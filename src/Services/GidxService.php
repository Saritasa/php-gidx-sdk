<?php

namespace GidxSDK\Services;

use GidxSDK\Enums\GidxDocumentParams;
use GidxSDK\Enums\GidxParams;
use GidxSDK\Enums\GidxPaymentParams;
use GidxSDK\Dto\CreateSessionDto;
use GidxSDK\Dto\CreateWithdrawDto;
use GidxSDK\Dto\Response\CashWithdrawResponse;
use GidxSDK\Dto\Response\CoinsWithdrawResponse;
use GidxSDK\Dto\Response\CreateSessionResponseDto;
use GidxSDK\Dto\Response\CreateWithdrawResponse;
use GidxSDK\Dto\Response\CustomerProfileResponseDto;
use App\Dto\Transactions\LockedTransaction;
use App\Dto\Transactions\TransactionDto;
use GidxSDK\Enums\GidxCurrencyCodes;
use GidxSDK\Enums\GidxDocumentStatuses;
use GidxSDK\Enums\GidxPayActionCodes;
use GidxSDK\Enums\GidxPaymentStatusCodes;
use GidxSDK\Enums\GidxServiceTypes;
use GidxSDK\Enums\GidxSessionTypes;
use GidxSDK\Enums\PaymentActionTypes;
use GidxSDK\Enums\PaymentRequestStatuses;
use GidxSDK\Enums\PaymentRequestTypes;
use GidxSDK\Enums\TransactionTypes;
use App\Exceptions\Lock\AcquireLockFailedException;
use App\Exceptions\Payments\PaymentException;
use App\Exceptions\Payments\PaymentStatusException;
use App\Helpers\LogToChannels;
use GidxSDK\Models\GidxSession;
use GidxSDK\Models\GidxSessionResponse;
use GidxSDK\Models\PaymentRequest;
use GidxSDK\Models\PaymentStatusTracking;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Transactions\ITransactionsService;
use App\Services\Transactions\TransactionsService;
use Dingo\Api\Http\Response;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Service to interact with GIDX apis.
 */
class GidxService
{
    protected const LOG_ERROR_GIDX_CALLBACK = 'GidxCallbackError';
    protected const LOG_GIDX_PAYMENT = 'GidxPaymentLog';

    /**
     * Gidx Client
     *
     * @var GidxClient
     */
    public $client;

    /**
     * Log channels.
     *
     * @var LogToChannels
     */
    private $log;

    /**
     * Transaction service.
     *
     * @var ITransactionsService
     */
    protected $transactionService;

    /**
     * GidxService constructor.
     *
     * @param LogToChannels $logToChannels Logger
     * @param ITransactionsService $transactionService Transaction service
     */
    public function __construct(LogToChannels $logToChannels, ITransactionsService $transactionService)
    {
        $this->client = new GidxClient();
        $this->log = $logToChannels;
        $this->transactionService = $transactionService;
    }

    /**
     * Get customer profile from Gidx.
     *
     * @param User $user User model
     *
     * @return CustomerProfileResponseDto
     *
     * @throws Throwable
     */
    public function getCustomerProfile(User $user): CustomerProfileResponseDto
    {
        if (!$user->hasGidxCustomerId()) {
            throw new NotFoundHttpException(trans('gidx.customer_not_found'));
        }

        $jsonResponse = $this->client->getCustomerProfile($user->getOrCreateGidxId());

        return new CustomerProfileResponseDto($jsonResponse);
    }

    /**
     * Create payment session.
     *
     * @param User $user User model
     * @param CreateSessionDto $sessionDto Session data object
     *
     * @return Arrayable
     *
     * @throws Throwable
     */
    public function createSession(User $user, CreateSessionDto $sessionDto): Arrayable
    {
        $location = $sessionDto->device_gps ? $sessionDto->device_gps->toArray() : null;
        $ip = $sessionDto->customer_ip_address;
        $response = [];

        switch ($sessionDto->type) {
            case GidxSessionTypes::PROFILE:
                // Prepare params for create customer session
                $params = $this->createStandardSessionRequest($user->getOrCreateGidxId(), $ip, $location);

                // Store create session request
                $this->storeCreateSessionRequest($user->id, GidxServiceTypes::CUSTOMER_REGISTRATION, $params);

                // Create session request
                $response = $this->client->createProfileSession($params);

                // Store response request
                $this->storeCreateSessionResponse(
                    $user->id,
                    $user->getOrCreateGidxId(),
                    GidxServiceTypes::CUSTOMER_REGISTRATION,
                    $response
                );
                break;
            case GidxSessionTypes::PAY:
                $response = $this->createDepositSession($user, $sessionDto->amount, $ip, $location);
                break;
            case GidxSessionTypes::PAYOUT:
                if (!$sessionDto->amount || $sessionDto->amount <= 0) {
                    throw new InvalidArgumentException();
                }
                $response = $this->client->createPayoutSession($sessionDto->amount);
                break;
        }
        return new CreateSessionResponseDto($response);
    }

    /**
     * Create deposit session.
     *
     * @param User $user User perform
     * @param float $amount Amount
     * @param string $ip IP
     * @param mixed[]|null $location Location
     *
     * @return mixed[]
     *
     * @throws Throwable
     */
    public function createDepositSession(User $user, float $amount, string $ip, ?array $location): array
    {
        if (!$amount || $amount <= 0) {
            throw new PaymentException('Payment amount invalid');
        }

        $response = DB::transaction(function () use ($user, $amount, $ip, $location) {
            // Create payment session request
            $params = $this->createPaymentSessionRequest(
                $user->getOrCreateGidxId(),
                $amount,
                GidxPayActionCodes::PAY,
                $ip,
                $location
            );

            // Store create session request
            $gidxSession = $this->storeCreateSessionRequest($user->id, GidxServiceTypes::PAYMENT, $params);

            // Store payment request
            $paymentRequest = $this->storePaymentRequest(
                $user->id,
                PaymentRequestStatuses::NEW,
                PaymentRequestTypes::DEPOSIT,
                $gidxSession->id,
                $params
            );

            // Init payment status tracking
            $this->storePaymentStatusTracking($paymentRequest, $paymentRequest->user_id);

            return array_merge($this->client->createPaySession($params), [
                'ID' => $paymentRequest->id,
            ]);
        });

        // Store response request
        $this->storeCreateSessionResponse(
            $user->id,
            $user->getOrCreateGidxId(),
            GidxServiceTypes::PAYMENT,
            $response
        );

        return $response;
    }

    /**
     * Create profile session request.
     *
     * @param string $merchantCustomerId Merchant customer ID
     * @param string $ip IP address
     * @param mixed[] $location Device GPS
     *
     * @return mixed[]
     */
    public function createStandardSessionRequest(string $merchantCustomerId, string $ip, ?array $location): array
    {
        $params = [
            GidxParams::MERCHANT_SESSION_ID => Str::uuid()->toString(),
            GidxParams::MERCHANT_CUSTOMER_ID => $merchantCustomerId,
            GidxParams::CUSTOMER_IP_ADDRESS => $ip,
            GidxParams::CALLBACK_URL => $this->client->getCallbackUrl(),
        ];

        if ($location) {
            $params[GidxParams::DEVICE_GPS] = $location;
        }

        return $params;
    }

    /**
     * Create payment session request params.
     *
     * @param string $merchantCustomerId Customer ID
     * @param float $amount amount
     * @param string $payActionType Pay action type, @see GidxPayActionCodes::class
     * @param string $ip IP
     * @param mixed[]|null $location Location
     *
     * @return mixed[]
     */
    protected function createPaymentSessionRequest(
        string $merchantCustomerId,
        float $amount,
        string $payActionType,
        string $ip,
        ?array $location
    ): array {
        // Prepare params for create customer session
        $standardSessionParams = $this->createStandardSessionRequest($merchantCustomerId, $ip, $location);

        // Create transaction ID
        $transactionId = Str::uuid()->toString();

        // Pay params
        $payParams = [
            'MerchantOrderID' => $transactionId,
            'MerchantTransactionID' => $transactionId,
            'PayActionCode' => $payActionType,
            'CashierPaymentAmount' => [
                'PaymentAmount' => $amount,
                'PaymentAmountOverride' => true,
                'BonusAmount' => 0,
                'BonusAmountOverride' => true,
                'BonusDetails' => '',
                'PaymentCurrencyCode' => GidxCurrencyCodes::USD,
            ],
        ];

        return array_merge($standardSessionParams, $payParams);
    }

    /**
     * Store create session request.
     *
     * @param int $userId User ID
     * @param string $serviceType Gidx service type
     * @param mixed[] $params Params request
     *
     * @return GidxSession
     */
    public function storeCreateSessionRequest(int $userId, string $serviceType, array $params): GidxSession
    {
        $gidxSession = new GidxSession();
        $gidxSession->user_id = $userId;
        $gidxSession->service_type = $serviceType;
        $gidxSession->merchant_session_id = $params[GidxParams::MERCHANT_SESSION_ID];
        $gidxSession->merchant_transaction_id = isset($params[GidxParams::MERCHANT_TRANSACTION_ID])
            ? $params[GidxParams::MERCHANT_TRANSACTION_ID] : null;
        $gidxSession->merchant_customer_id = $params[GidxParams::MERCHANT_CUSTOMER_ID];
        $gidxSession->ip_address = $params[GidxParams::CUSTOMER_IP_ADDRESS];
        $gidxSession->device_location = isset($params[GidxParams::DEVICE_GPS])
            ? json_encode($params[GidxParams::DEVICE_GPS]) : null;
        $gidxSession->request_raw = json_encode($params);
        $gidxSession->save();

        return $gidxSession;
    }

    /**
     * Store payment request.
     *
     * @param int $userId User ID
     * @param string $status Status
     * @param string $type Type of payment
     * @param int|null $gidxSessionId Gidx session id
     * @param mixed[] $params Params request
     *
     * @return PaymentRequest
     */
    public function storePaymentRequest(
        int $userId,
        string $status,
        string $type,
        ?int $gidxSessionId = null,
        array $params = []
    ): PaymentRequest {
        $paymentRequest = new PaymentRequest();
        $paymentRequest->user_id = $userId;
        $paymentRequest->status = $status;
        $paymentRequest->type = $type;
        $paymentRequest->merchant_transaction_id = $params[GidxParams::MERCHANT_TRANSACTION_ID];
        $paymentRequest->amount = $params['CashierPaymentAmount']['PaymentAmount'];
        $paymentRequest->currency = $params['CashierPaymentAmount']['PaymentCurrencyCode'];
        $paymentRequest->gidx_session_id = $gidxSessionId;
        $paymentRequest->save();

        return $paymentRequest;
    }

    /**
     * Store payment status tracking.
     *
     * @param PaymentRequest $paymentRequest Payment request
     * @param int|null $actionBy Who perform action to change status
     * @param string|null $oldStatus Old status
     * @param string $actionType Action type of payment
     *
     * @return PaymentStatusTracking
     */
    public function storePaymentStatusTracking(
        PaymentRequest $paymentRequest,
        ?int $actionBy = null,
        ?string $oldStatus = null,
        ?string $actionType = PaymentActionTypes::AUTOMATIC
    ): PaymentStatusTracking {
        $paymentStatusTracking = new PaymentStatusTracking();
        $paymentStatusTracking->payment_request_id = $paymentRequest->id;
        $paymentStatusTracking->action_by = $actionBy;
        $paymentStatusTracking->action_type = $actionBy ? PaymentActionTypes::MANUAL : $actionType;
        $paymentStatusTracking->old_status = $oldStatus;
        $paymentStatusTracking->status = $paymentRequest->status;
        $paymentStatusTracking->save();

        return $paymentStatusTracking;
    }

    /**
     * Store create session response.
     *
     * @param int $userId User ID
     * @param string $merchantCustomerId Merchant customer ID
     * @param string $serviceType Service type
     * @param mixed[] $response Response params
     *
     * @return GidxSessionResponse
     */
    public function storeCreateSessionResponse(
        int $userId,
        string $merchantCustomerId,
        string $serviceType,
        array $response
    ): GidxSessionResponse {
        $gidxSessionResponse = new GidxSessionResponse();
        $gidxSessionResponse->user_id = $userId;
        $gidxSessionResponse->service_type = $serviceType;
        $gidxSessionResponse->merchant_customer_id = $merchantCustomerId;
        $gidxSessionResponse->merchant_session_id = $response[GidxParams::MERCHANT_SESSION_ID];
        $gidxSessionResponse->merchant_transaction_id = isset($response[GidxParams::MERCHANT_TRANSACTION_ID])
            ? $response[GidxParams::MERCHANT_TRANSACTION_ID] : null;
        $gidxSessionResponse->status_code = $response[GidxParams::RESPONSE_CODE];
        $gidxSessionResponse->status_message = $response[GidxParams::RESPONSE_MESSAGE];
        $gidxSessionResponse->session_score = $response[GidxParams::SESSION_SCORE];
        $gidxSessionResponse->response_raw = json_encode($response);
        $gidxSessionResponse->save();

        return $gidxSessionResponse;
    }

    /**
     * Get status of a payment session.
     *
     * @param string $merchantSessionID Merchant session ID
     * @param mixed[] $options Optional params (ApiKey, MerchantID, ...)
     *
     * @return mixed[]|null
     *
     * @throws Throwable
     */
    public function getPaymentSessionStatus(string $merchantSessionID, array $options = []): ?array
    {
        return $this->client->getPaymentSessionStatus($merchantSessionID, $options);
    }

    /**
     * Get payment details.
     *
     * @param string $merchantSessionID Merchant session ID
     * @param string $merchantTransactionID Merchant transaction ID
     * @param mixed[] $options Optional params (ApiKey, MerchantID, ...)
     *
     * @return mixed[]|null
     *
     * @throws Throwable
     */
    public function getPaymentDetails(
        string $merchantSessionID,
        string $merchantTransactionID,
        array $options = []
    ): ?array {
        return $this->client->getPaymentDetails($merchantSessionID, $merchantTransactionID, $options);
    }

    /**
     * Upload document to Gidx.
     *
     * @param User $user User model
     * @param UploadedFile $documentFile Upload file object
     * @param int $categoryType Document category type
     *
     * @throws Throwable
     */
    public function uploadDocument(User $user, UploadedFile $documentFile, int $categoryType): void
    {
        $params = [
            'file' => curl_file_create(
                $documentFile->getRealPath(),
                $documentFile->getMimeType(),
                $documentFile->getClientOriginalName()
            ),
            'json' => [
                GidxParams::MERCHANT_SESSION_ID => Str::uuid()->toString(),
                GidxParams::MERCHANT_CUSTOMER_ID => $user->getOrCreateGidxId(),
                GidxDocumentParams::CATEGORY_TYPE => $categoryType,
                GidxDocumentParams::DOCUMENT_STATUS => GidxDocumentStatuses::NOT_REVIEWED,
            ],
        ];

        $this->client->uploadDocument($params);
    }

    /**
     * Handle call back from GIDX.
     *
     * @param mixed[] $payload Call back payload
     *
     * @return void
     *
     * @throws AcquireLockFailedException
     * @throws Throwable
     */
    public function handleCallback(array $payload): void
    {
        $payload = isset($payload['result']) ? json_decode($payload['result'], true) : $payload;

        $gidxServiceType = Arr::get($payload, GidxParams::SERVICE_TYPE);

        switch ($gidxServiceType) {
            case GidxServiceTypes::PAYMENT:
                $this->handlePaymentCallback($payload);
                break;
            case GidxServiceTypes::CUSTOMER_REGISTRATION:
                // Handle customer registration callback
                break;
        }
    }

    /**
     * Handle payment callback.
     *
     * @param mixed[] $payload Call back payload
     *
     * @return void
     *
     * @throws AcquireLockFailedException
     * @throws Throwable
     */
    public function handlePaymentCallback(array $payload): void
    {
        $merchantTransactionId = Arr::get($payload, GidxParams::MERCHANT_TRANSACTION_ID);

        /* @var PaymentRequest $paymentRequest */
        $paymentRequest = $this->getLastPaymentRequestByMerchantTransactionId($merchantTransactionId);

        if (!$paymentRequest) {
            $this->log->error('Payment request not found', self::LOG_ERROR_GIDX_CALLBACK, $payload);
            return;
        }

        switch ($paymentRequest[PaymentRequest::TYPE]) {
            case PaymentRequestTypes::DEPOSIT:
                // Handle deposit callback
                $this->handleDepositCallback($paymentRequest, $payload);
                break;
            case PaymentRequestTypes::WITHDRAW:
                // Handle withdraw callback
                $this->handleWithdrawCallback($paymentRequest, $payload);
                break;
        }
    }

    /**
     * Get payment details data from gidx.
     *
     * @param string $merchantSessionId
     * @param string $merchantTransactionId
     *
     * @return mixed[]
     * @throws Throwable
     */
    protected function getGidxPaymentDetails(string $merchantSessionId, string $merchantTransactionId): array
    {
        $gidxPaymentDetailResponse = $this->client->getPaymentDetails($merchantSessionId, $merchantTransactionId);

        if (!isset($gidxPaymentDetailResponse[GidxPaymentParams::PAYMENT_DETAILS])) {
            $this->log->error(
                'Payment details not found',
                self::LOG_ERROR_GIDX_CALLBACK,
                compact('merchantSessionId', 'merchantTransactionId')
            );

            return [];
        }

        /**
         * "PaymentDetails" is array contains list of payment items.
         * In our case, only first item is available.
         */
        return (array) Arr::first($gidxPaymentDetailResponse[GidxPaymentParams::PAYMENT_DETAILS]);
    }

    /**
     * Handle deposit call back from GIDX.
     *
     * @param PaymentRequest $paymentRequest Payment request object
     * @param mixed[] $payload Call back payload
     *
     * @throws AcquireLockFailedException
     *
     * @throws Throwable
     */
    public function handleDepositCallback(PaymentRequest $paymentRequest, array $payload): void
    {
        $gidxPaymentDetailResponse = $this->client->getPaymentDetails(
            $payload[GidxParams::MERCHANT_SESSION_ID],
            $paymentRequest->merchant_transaction_id
        );

        if (empty($gidxPaymentDetailResponse[GidxPaymentParams::PAYMENT_DETAILS])) {
            $this->log->error('Payment detail not found', self::LOG_ERROR_GIDX_CALLBACK, $payload);
            return;
        }

        // Get payment detail from Gidx
        $gidxPaymentDetail = Arr::first($gidxPaymentDetailResponse[GidxPaymentParams::PAYMENT_DETAILS]);

        $this->processPaymentRequestByGidxPaymentDetail(
            $paymentRequest,
            $gidxPaymentDetail,
            PaymentActionTypes::GIDX_CALLBACK
        );
    }

    /**
     * Process payment request with Gidx payment detail.
     *
     * @param PaymentRequest $paymentRequest Payment Request
     * @param mixed[] $gidxPaymentDetail Gidx payment detail
     * @param string|null $actionType Action type
     *
     * @throws AcquireLockFailedException
     */
    public function processPaymentRequestByGidxPaymentDetail(
        PaymentRequest $paymentRequest,
        array $gidxPaymentDetail,
        ?string $actionType = PaymentActionTypes::AUTOMATIC
    ): void {
        switch ($paymentRequest->type) {
            case PaymentRequestTypes::DEPOSIT:
                $this->processTransactionForDeposit($paymentRequest, $gidxPaymentDetail, $actionType);
                break;
            case PaymentRequestTypes::WITHDRAW:
                // Handle withdraw payment request
            case PaymentRequestTypes::REFUND:
                // Handle withdraw payment request
        }
    }

    /**
     * Process add transaction for deposit.
     *
     * @param PaymentRequest $paymentRequest Payment request
     * @param mixed[] $gidxPaymentDetail Gidx payment detail
     * @param string|null $actionType Action type
     *
     * @throws AcquireLockFailedException
     */
    public function processTransactionForDeposit(
        PaymentRequest $paymentRequest,
        array $gidxPaymentDetail,
        ?string $actionType = PaymentActionTypes::AUTOMATIC
    ): void {
        // Mapping payment status with Gidx and if not exits default is pending
        $newPaymentStatus = isset($gidxPaymentDetail[GidxPaymentParams::PAYMENT_STATUS_CODE])
            ? $this->mapPaymentStatusWithGidxPaymentStatusCode(
                $gidxPaymentDetail[GidxPaymentParams::PAYMENT_STATUS_CODE]
            )
            : PaymentRequestStatuses::PENDING;

        $currentPaymentStatus = $paymentRequest->status;

        if ($newPaymentStatus === PaymentRequestStatuses::COMPLETED) {
            /* @var LockedTransaction $transaction */
            $lockedTransaction = $this->processTransactionCallback(
                $paymentRequest,
                TransactionTypes::COINS_ORDER_CREDIT
            );
            $paymentRequest->transaction_id = $lockedTransaction->transaction->id;

            $this->log->info('Payment request updated transaction ID', self::LOG_GIDX_PAYMENT, [
                PaymentRequest::TRANSACTION_ID => $lockedTransaction->transaction->id,
            ]);
        }

        // Update payment status
        $paymentRequest->status = $newPaymentStatus;
        $paymentRequest->method_type = $gidxPaymentDetail[GidxPaymentParams::PAYMENT_METHOD_TYPE];
        $paymentRequest->save();

        $this->log->info('Payment request updated success', self::LOG_GIDX_PAYMENT, [
            PaymentRequest::MERCHANT_TRANSACTION_ID => $paymentRequest->merchant_transaction_id,
        ]);

        // Store payment status tracking
        $this->storePaymentStatusTracking($paymentRequest, null, $currentPaymentStatus, $actionType);

        $this->log->info('Payment status tracking updated success', self::LOG_GIDX_PAYMENT, [
            PaymentRequest::MERCHANT_TRANSACTION_ID => $paymentRequest->merchant_transaction_id,
        ]);
    }

    /**
     * Handle withdraw call back from GIDX.
     *
     * @param PaymentRequest $paymentRequest Payment request object
     * @param mixed[] $payload Call back payload
     *
     * @throws AcquireLockFailedException
     *
     * @throws Throwable
     */
    public function handleWithdrawCallback(PaymentRequest $paymentRequest, array $payload): void
    {
        // Get payment detail from Gidx
        $gidxPaymentDetail = $this->getGidxPaymentDetails(
            $payload[GidxParams::MERCHANT_SESSION_ID],
            $paymentRequest->merchant_transaction_id
        );

        // Update payment method type.
        $paymentRequest->method_type = $gidxPaymentDetail[GidxPaymentParams::PAYMENT_METHOD_TYPE];
        $paymentRequest->save();

        $lockedTransaction = null;
        try {
            DB::transaction(function () use ($paymentRequest, &$lockedTransaction) {
                // Mapping payment status with Gidx and if not exits default is pending
                $newPaymentStatus = isset($gidxPaymentDetail[GidxPaymentParams::PAYMENT_STATUS_CODE])
                    ? $this->mapPaymentStatusWithGidxPaymentStatusCode(
                        $gidxPaymentDetail[GidxPaymentParams::PAYMENT_STATUS_CODE]
                    )
                    : PaymentRequestStatuses::PENDING;

                if (in_array($newPaymentStatus, [PaymentRequestStatuses::FAILED, PaymentRequestStatuses::REVERSED])) {
                    // Make reversal transaction for payment request.
                    if ($paymentRequest->transaction_id && is_null($paymentRequest->reversal_transaction_id)) {
                        $lockedTransaction = $this->transactionService->refund($paymentRequest->transaction);
                        $paymentRequest->reversal_transaction_id = $lockedTransaction->transaction->id;
                        $paymentRequest->save();
                    }
                }

                $currentPaymentStatus = $paymentRequest->status;
                if ($newPaymentStatus === $currentPaymentStatus) {
                    // Do nothing if status not changed.
                    return;
                }

                // Update payment status
                $paymentRequest->status = $newPaymentStatus;
                $paymentRequest->save();

                $this->log->info('Payment request updated success',
                    self::LOG_GIDX_CALLBACK,
                    [
                        PaymentRequest::MERCHANT_TRANSACTION_ID => $paymentRequest->merchant_transaction_id,
                    ]);

                // Store payment status tracking
                $this->storePaymentStatusTracking(
                    $paymentRequest,
                    null,
                    $currentPaymentStatus,
                    PaymentActionTypes::GIDX_CALLBACK
                );

                $this->log->info('Payment status tracking updated success',
                    self::LOG_GIDX_CALLBACK,
                    [
                        PaymentRequest::MERCHANT_TRANSACTION_ID => $paymentRequest->merchant_transaction_id,
                    ]);
            });
        } finally {
            if ($lockedTransaction) {
                $lockedTransaction->lock->release();
            }
        }
    }

    /**
     * Process transaction call back from GIDX.
     *
     * @param PaymentRequest $paymentRequest Payment request
     * @param string $transactionType Transaction type
     *
     * @return LockedTransaction
     *
     * @throws AcquireLockFailedException
     */
    public function processTransactionCallback(
        PaymentRequest $paymentRequest,
        string $transactionType
    ): LockedTransaction {
        // Check transaction was handled or not.
        if ($this->transactionService->getTransactionByExtTransactionNo($paymentRequest->merchant_transaction_id)) {
            $this->log->error(
                'Transaction was created.',
                self::LOG_ERROR_GIDX_CALLBACK,
                [
                    PaymentRequest::MERCHANT_TRANSACTION_ID => $paymentRequest->merchant_transaction_id,
                ]
            );

            throw new PaymentException(trans('gidx.payment_transaction_handled'));
        }

        $transactionDto = new TransactionDto([
            Transaction::USER_ID => $paymentRequest->user_id,
            Transaction::TYPE => $transactionType,
            Transaction::AMOUNT => $paymentRequest->amount,
            Transaction::EXT_TRANSACTION_ID => $paymentRequest->merchant_transaction_id,
        ]);

        /* @var User $user */
        $user = User::query()->find($paymentRequest->user_id);

        return $this->transactionService->createTransaction($user, $transactionDto);
    }

    /**
     * Mapping payment status with Gidx payment status by code.
     *
     * @param int $paymentStatusCode Payment status code
     *
     * @return string
     */
    public function mapPaymentStatusWithGidxPaymentStatusCode(int $paymentStatusCode): string
    {
        switch ($paymentStatusCode) {
            case GidxPaymentStatusCodes::PAYMENT_NOT_FOUND:
            case GidxPaymentStatusCodes::INELIGIBLE:
            case GidxPaymentStatusCodes::FAILED:
                return PaymentRequestStatuses::FAILED;
            case GidxPaymentStatusCodes::PENDING:
            case GidxPaymentStatusCodes::PROCESSING:
                return PaymentRequestStatuses::PENDING;
            case GidxPaymentStatusCodes::COMPLETE:
                return PaymentRequestStatuses::COMPLETED;
            case GidxPaymentStatusCodes::REVERSED:
                return PaymentRequestStatuses::REVERSED;
        }

        throw new PaymentStatusException(trans('gidx.payment_status_not_found'));
    }

    /**
     * Get last payment request by merchant transaction ID.
     *
     * @param string $merchantTransactionId Merchant transaction ID.
     *
     * @return PaymentRequest|null
     */
    public function getLastPaymentRequestByMerchantTransactionId(string $merchantTransactionId): ?PaymentRequest
    {
        return PaymentRequest::query()->where(PaymentRequest::MERCHANT_TRANSACTION_ID, $merchantTransactionId)
            ->orderByDesc(PaymentRequest::ID)->first();
    }

    /**
     * Create withdraw requests for given amount.
     *
     * @param User $user User model
     * @param CreateWithdrawDto $createWithdrawDto Create withdraw requests dto
     *
     * @return CreateWithdrawResponse
     *
     * @throws Throwable
     */
    public function createWithdrawRequests(User $user, CreateWithdrawDto $createWithdrawDto): CreateWithdrawResponse
    {
        $location = $createWithdrawDto->device_gps ? $createWithdrawDto->device_gps->toArray() : null;
        $ip = $createWithdrawDto->customer_ip_address;

        // Split amount and validate balance.
        $userBalance = TransactionsService::getUserBalance($user->id);
        $amountDto = TransactionsService::splitWithdrawAmount($createWithdrawDto->amount, $userBalance);

        // Validate current amounts with preview amounts.
        if (floatval($amountDto->coins_amount) !== floatval($createWithdrawDto->coins_amount)
            || floatval($amountDto->cash_amount) !== floatval($createWithdrawDto->cash_amount)
        ) {
            abort(Response::HTTP_CONFLICT, trans('transactions.balance_was_changed'));
        }

        // Split withdraw amount to coins amount and cash amount if possible.
        // Then create payment request for each type.
        $response = new CreateWithdrawResponse([]);
        if ($amountDto->cash_amount) {
            try {
                $response->cashResponse = $this->createCashWithdrawRequest(
                    $user,
                    $amountDto->cash_amount,
                    $ip,
                    $location
                );
            } catch (Throwable $e) {
                $response->cashResponse = new CashWithdrawResponse([
                    CashWithdrawResponse::ERROR_MESSAGE => $e->getMessage()
                ]);
            }
        }
        if ($amountDto->coins_amount) {
            $response->coinsResponse = new CoinsWithdrawResponse([]);
            try {
                $response->coinsResponse->paymentRequest = $this->createCoinsWithdrawRequest($user, $amountDto->coins_amount);
            } catch (Throwable $e) {
                $response->coinsResponse->errorMessage = $e->getMessage();
            }
        }

        return $response;
    }

    /**
     * Create coins withdraw request.
     *
     * @param User $user User model
     * @param float $amount Amount to withdraw
     * @param string $ip User ip
     * @param mixed[]|null $location Location data
     *
     * @return CashWithdrawResponse
     * @throws Throwable
     */
    public function createCashWithdrawRequest(User $user, float $amount, string $ip, ?array $location): CashWithdrawResponse
    {
        /* @var LockedTransaction $lockedTransaction */
        $lockedTransaction = null;
        try {
            return DB::transaction(function () use ($user, $amount, &$lockedTransaction, $ip, $location) {
                // Create payout session request
                $params = $this->createPaymentSessionRequest(
                    $user->getOrCreateGidxId(),
                    $amount,
                    GidxPayActionCodes::PAYOUT,
                    $ip,
                    $location
                );

                // Store create session request
                $gidxSession = $this->storeCreateSessionRequest($user->id, GidxServiceTypes::PAYMENT, $params);

                // Store payment request
                $paymentRequest = $this->storePaymentRequest(
                    $user->id,
                    PaymentRequestStatuses::PENDING,
                    PaymentRequestTypes::WITHDRAW,
                    $gidxSession->id,
                    $params
                );

                // Init payment status tracking
                $this->storePaymentStatusTracking($paymentRequest, $paymentRequest->user_id);

                $transactionDto = new TransactionDto([
                    Transaction::USER_ID => $paymentRequest->user_id,
                    Transaction::TYPE => TransactionTypes::CASH_WITHDRAW_DEBIT,
                    Transaction::AMOUNT => -$paymentRequest->amount,
                    Transaction::EXT_TRANSACTION_ID => $paymentRequest->merchant_transaction_id,
                ]);

                $lockedTransaction = $this->transactionService->createTransaction($user, $transactionDto);
                $paymentRequest->transaction_id = $lockedTransaction->transaction->id;
                $paymentRequest->save();

                $response = new CashWithdrawResponse([]);
                $response->paymentRequest = $paymentRequest;
                $response->sessionResponse = array_merge(
                    $this->client->createPaySession($params),
                    [
                        'ID' => $paymentRequest->id,
                    ]
                );

                // Store response request
                $this->storeCreateSessionResponse(
                    $user->id,
                    $user->getOrCreateGidxId(),
                    GidxServiceTypes::PAYMENT,
                    $response->sessionResponse
                );

                return $response;
            });
        } finally {
            if ($lockedTransaction) {
                $lockedTransaction->lock->release();
            }
        }
    }

    /**
     * Create coins withdraw request.
     *
     * @param User $user User model
     * @param float $amount Amount to withdraw
     *
     * @return PaymentRequest
     *
     * @throws Throwable
     */
    public function createCoinsWithdrawRequest(User $user, float $amount): PaymentRequest
    {
        /* @var LockedTransaction $lockedTransaction */
        $lockedTransaction = null;
        try {
            return DB::transaction(function () use ($user, $amount, &$lockedTransaction) {
                $paymentRequest = new PaymentRequest();
                $paymentRequest->user_id = $user->id;
                $paymentRequest->status = PaymentRequestStatuses::PENDING;
                $paymentRequest->type = PaymentRequestTypes::REFUND;
                $paymentRequest->amount = $amount;

                $transactionDto = new TransactionDto([
                    Transaction::USER_ID => $paymentRequest->user_id,
                    Transaction::TYPE => TransactionTypes::COINS_WITHDRAW_DEBIT,
                    Transaction::AMOUNT => -$paymentRequest->amount,
                ]);

                $lockedTransaction = $this->transactionService->createTransaction($user, $transactionDto);

                // Store payment request and tracking status.
                $paymentRequest->transaction_id = $lockedTransaction->transaction->id;
                $paymentRequest->save();
                $this->storePaymentStatusTracking($paymentRequest, $paymentRequest->user_id);

                return $paymentRequest;
            });
        } finally {
            if ($lockedTransaction) {
                $lockedTransaction->lock->release();
            }
        }
    }
}
