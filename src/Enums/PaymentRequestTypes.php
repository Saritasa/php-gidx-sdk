<?php

namespace GidxSDK\Enums;

use Saritasa\Enum;

/**
 * Payment request types.
 */
class PaymentRequestTypes extends Enum
{
    public const DEPOSIT = 'deposit';
    public const WITHDRAW = 'withdraw';
    public const REFUND = 'refund';
}
