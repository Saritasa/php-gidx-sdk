<?php

namespace GidxSDK\Services;

use GidxSDK\Enums\GidxParams;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Client to interact with GIDX apis.
 */
class GidxClient
{
    private const CONTENT_TYPE = 'Content-Type';
    private const JSON = 'application/json';
    private const MULTIPART = 'multipart/form-data';
    private const CUSTOMER_IDENTIFY_MONITOR = 'v3.0/api/CustomerIdentity/CustomerMonitor';
    private const CUSTOMER_IDENTIFY_PROFILE = 'v3.0/api/CustomerIdentity/CustomerProfile';
    private const WEB_REG_CREATE_SESSION_URI = 'v3.0/api/WebReg/CreateSession';
    private const WEB_CASHIER_CREATE_SESSION_URI = 'v3.0/api/WebCashier/CreateSession';
    private const WEB_CASHIER_GET_SESSION_STATUS_URI = 'v3.0/api/WebCashier/WebCashierStatus';
    private const WEB_CASHIER_GET_PAYMENT_DETAILS_URI = 'v3.0/api/WebCashier/PaymentDetail';
    private const DOCUMENT_LIBRARY_REGISTRATION = 'v3.0/api/DocumentLibrary/DocumentRegistration';
    private const LOG_ERROR_REQUEST_CHANNEL = 'GidxErrorRequest';

    /**
     * Get customer profile from Gidx.
     *
     * @param string $customerId Customer ID
     *
     * @return mixed[]|null
     *
     * @throws Throwable
     */
    public function getCustomerProfile(string $customerId): ?array
    {
        $uri = $this->getBaseUri() . '/' . self::CUSTOMER_IDENTIFY_PROFILE;

        $params = [
            GidxParams::MERCHANT_SESSION_ID => Str::uuid()->toString(),
            GidxParams::MERCHANT_CUSTOMER_ID => $customerId,
        ];

        return $this->makeRequest(Request::METHOD_GET, $uri, array_merge($this->getBaseKey(), $params));
    }

    /**
     * Create session.
     *
     * @param string $uri uri
     * @param mixed[] $params other parameters
     *
     * @return mixed[]|null
     *
     * @throws Throwable
     */
    public function createSession(string $uri, array $params): ?array
    {
        return $this->makeRequest(Request::METHOD_POST, $uri, array_merge($this->getBaseKey(), $params));
    }

    /**
     * Create profile session.
     *
     * @param mixed[] $params Params request
     *
     * @return mixed[]|null
     *
     * @throws Throwable
     */
    public function createProfileSession(array $params): ?array
    {
        $uri = $this->getBaseUri() . '/' . self::WEB_REG_CREATE_SESSION_URI;

        return $this->createSession($uri, $params);
    }

    /**
     * Create payment session.
     *
     * @param mixed[] $params Pay params
     *
     * @return mixed[]|null
     *
     * @throws Throwable
     */
    public function createPaySession(array $params): ?array
    {
        $uri = $this->getBaseUri() . '/' . self::WEB_CASHIER_CREATE_SESSION_URI;

        return $this->createSession($uri, $params);
    }

    /**
     * Create pay out session.
     *
     * @param float $amount amount
     *
     * @return mixed[]|null
     *
     * @throws Throwable
     */
    public function createPayoutSession(float $amount) : ?array
    {
        $uri = $this->getBaseUri() . '/' . self::WEB_CASHIER_CREATE_SESSION_URI;
        $params = [
            'PayActionCode' => 'PAYOUT',
            'CashierPaymentAmount' => [
                'PaymentAmount' => $amount,
                'PaymentAmountOverride' => true,
                'BonusAmount' => 0,
                'BonusAmountOverride' => true,
                'BonusDetails' => '',
                'PaymentCurrencyCode' => 'USD',
            ],
        ];
        return $this->createSession($uri, $params);
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
    public function getPaymentSessionStatus(string $merchantSessionID, array $options = []) : ?array
    {
        $params = $options;
        $params['MerchantSessionID'] = $merchantSessionID;
        $uri = $this->getBaseUri() . '/' . self::WEB_CASHIER_GET_SESSION_STATUS_URI;

        return $this->makeRequest(Request::METHOD_GET, $uri, $params);
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
        $params = $options;
        $params['MerchantSessionID'] = $merchantSessionID;
        $params['MerchantTransactionID'] = $merchantTransactionID;
        $uri = $this->getBaseUri() . '/' . self::WEB_CASHIER_GET_PAYMENT_DETAILS_URI;

        return $this->makeRequest(Request::METHOD_GET, $uri, array_merge($this->getBaseKey(), $params));
    }

    /**
     * Upload document to Gidx.
     *
     * @param mixed[] $params Params request
     *
     * @return mixed[]|null
     *
     * @throws Throwable
     */
    public function uploadDocument(array $params): ?array
    {
        $uri = $this->getBaseUri() . '/' . self::DOCUMENT_LIBRARY_REGISTRATION;
        $headers = [
            self::CONTENT_TYPE => 'multipart/form-data',
        ];

        $params['json'] = json_encode(array_merge($this->getBaseKey(), $params['json']));

        return $this->makeRequest(Request::METHOD_POST, $uri, $params, $headers);
    }

    /**
     * Returns base uri.
     *
     * @return string
     */
    public function getBaseUri(): string
    {
        return config('gidx.base_uri');
    }

    /**
     * Returns callback uri.
     *
     * @return string
     */
    public function getCallbackUrl(): string
    {
        return config('gidx.callback_url');
    }

    /**
     * Make request to GIDX system.
     *
     * @param string $method HTTP method
     * @param string $uri The uri to make request
     * @param mixed[] $data Request data in structure {"field_name" => "value"}
     * @param mixed[] $headers Request headers in structure {"header_name" => "value"}
     *
     * @return mixed[]|null
     *
     * @throws Throwable
     */
    public function makeRequest(
        string $method,
        string $uri = '',
        array $data = [],
        array $headers = []
    ): ?array {
        // Attach query params to uri if method is GET.
        if ($method === Request::METHOD_GET) {
            $uri = $data ? sprintf("%s?%s", $uri, http_build_query($data)) : $uri;
            $postData = '';
        } else {
            // Set 'application/json' as default content type if not specified.
            if (empty($headers[self::CONTENT_TYPE])) {
                $headers[self::CONTENT_TYPE] = self::JSON;
            }
            $contentType = $headers[self::CONTENT_TYPE] ?? null;

            switch ($contentType) {
                case self::JSON:
                    $postData = json_encode($data);
                    break;
                case self::MULTIPART:
                    $postData = $data;
                    break;
                default:
                    $postData = http_build_query($data);
            }
        }

        $curl = curl_init();
        $curlOptions = [
            CURLOPT_URL => $uri,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => $this->toRawHeaders($headers),
        ];

        curl_setopt_array($curl, $curlOptions);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $jsonResponse = json_decode($response, true);
        curl_close($curl);

        if ($httpCode !== Response::HTTP_OK
            || (isset($jsonResponse['ResponseCode']) && $jsonResponse['ResponseCode'] !== 0)
        ) {
            $errorMsg = isset($jsonResponse['ResponseMessage']) ? $jsonResponse['ResponseMessage'] : 'Unknown error';

            Log::error($errorMsg, $jsonResponse);

            throw new Exception("Gidx error: $errorMsg, status code: $httpCode");
        }

        return $jsonResponse;
    }

    /**
     * Transform array of headers to raw headers.
     *
     * @param string[] $headers Array of headers
     *
     * @return string[]
     */
    private function toRawHeaders(array $headers): array
    {
        $rawHeaders = [];
        foreach ($headers as $key => $value) {
            $rawHeaders[] = "$key: $value";
        }

        return $rawHeaders;
    }

    /**
     * Get base API key.
     *
     * @return mixed[]
     */
    private function getBaseKey(): array
    {
        $mode = config('gidx.mode');
        return [
            'ApiKey' => config("gidx.$mode.api_key"),
            'MerchantID' => config("gidx.$mode.merchant_id"),
            'ProductTypeID' => config('gidx.product_type_id'),
            'DeviceTypeID' => config('gidx.device_type_id'),
            'ActivityTypeID' => config('gidx.activity_type_id'),
        ];
    }
}
