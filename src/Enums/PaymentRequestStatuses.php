<?php

namespace GidxSDK\Enums;

use Saritasa\Enum;

/**
 * Payment statuses request.
 */
class PaymentRequestStatuses extends Enum
{
    public const NEW = 'new';
    public const REJECTED = 'rejected';
    public const PENDING = 'pending';
    public const FAILED = 'failed';
    public const REVERSED = 'reversed';
    public const COMPLETED = 'completed';
}
