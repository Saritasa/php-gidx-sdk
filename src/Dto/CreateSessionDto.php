<?php

namespace GidxSDK\Dto;

use Illuminate\Contracts\Support\Arrayable;
use Saritasa\Dto;

/**
 * Gidx create session data transfer object.
 */
class CreateSessionDto extends Dto implements Arrayable
{
    public const CUSTOMER_IP_ADDRESS = 'customer_ip_address';
    public const DEVICE_GPS = 'device_gps';
    public const TYPE = 'type';
    public const AMOUNT = 'amount';

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
     * Type: profile, pay, payout
     *
     * @var string
     */
    public $type;

    /**
     * Amount
     *
     * @var float
     */
    public $amount;
}
