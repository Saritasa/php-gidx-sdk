<?php

namespace GidxSDK\Dto\Response;

use GidxSDK\Models\PaymentRequest;
use Illuminate\Contracts\Support\Arrayable;
use Saritasa\Dto;

/**
 * Response for create withdraw request.
 */
class CreateWithdrawResponse extends Dto implements Arrayable
{
    /**
     * Response for coins withdraw request.
     *
     * @var PaymentRequest
     */
    public $coinsResponse;

    /**
     * Response for cash withdraw request.
     *
     * @var CashWithdrawResponse
     */
    public $cashResponse;
}
