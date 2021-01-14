<?php

namespace GidxSDK\Enums;

use Saritasa\Enum;

/**
 * Action type in payment status tracking .
 */
class PaymentActionTypes extends Enum
{
    public const MANUAL = 'manual';
    public const AUTOMATIC = 'automatic';
    public const GIDX_CALLBACK = 'gidx_callback';
}
