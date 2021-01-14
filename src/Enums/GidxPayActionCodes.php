<?php

namespace GidxSDK\Enums;

use Saritasa\Enum;

/**
 * Gidx pay action codes.
 */
class GidxPayActionCodes extends Enum
{
    public const PAY = 'PAY';
    public const PAYOUT = 'PAYOUT';
    public const LOG = 'LOG';
}
