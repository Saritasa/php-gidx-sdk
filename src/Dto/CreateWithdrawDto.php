<?php

namespace GidxSDK\Dto;

use Illuminate\Contracts\Support\Arrayable;
use Saritasa\Dto;

/**
 * Create withdraw request data transfer object.
 */
class CreateWithdrawDto extends Dto implements Arrayable
{
    /**
     * Customer IP address.
     *
     * @var string
     */
    public $customer_ip_address;

    /**
     * Device GPS DTO object.
     *
     * @var DeviceGpsDto
     */
    public $device_gps;

    /**
     * Cash amount.
     *
     * @var float
     */
    public $cash_amount = 0.0;

    /**
     * Coins amount.
     *
     * @var float
     */
    public $coins_amount = 0.0;

    /**
     * Amount
     *
     * @var float
     */
    public $amount;
}
