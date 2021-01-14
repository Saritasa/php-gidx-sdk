<?php

namespace GidxSDK\Dto\Response;

use GidxSDK\Models\PaymentRequest;
use Illuminate\Contracts\Support\Arrayable;
use Saritasa\Dto;

/**
 * Response for create coins withdraw.
 */
class CoinsWithdrawResponse extends Dto implements Arrayable
{
    public const PAYMENT_REQUEST = 'paymentRequest';
    public const ERROR_MESSAGE = 'errorMessage';

    /**
     * Internal payment request.
     *
     * @var PaymentRequest
     */
    public $paymentRequest;

    /**
     * Error message when process withdraw.
     *
     * @var string
     */
    public $errorMessage;
}
