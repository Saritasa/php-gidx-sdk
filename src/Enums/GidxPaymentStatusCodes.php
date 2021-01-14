<?php

namespace GidxSDK\Enums;

use Saritasa\Enum;

/**
 * Payment statuses request.
 */
class GidxPaymentStatusCodes extends Enum
{
    public const PAYMENT_NOT_FOUND = -1;
    public const PENDING = 0;
    public const COMPLETE = 1;
    public const INELIGIBLE = 2;
    public const FAILED = 3;
    public const PROCESSING = 4;
    public const REVERSED = 5;
}
