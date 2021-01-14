<?php

namespace GidxSDK\Enums;

use Saritasa\Enum;

/**
 * Gidx payment method types request.
 */
class GidxPaymentMethodTypes extends Enum
{
    /**
     * Bank ACH Transaction
     */
    public const ACH = 'ACH';

    /**
     * Credit Card
     */
    public const CC = 'CC';
}
