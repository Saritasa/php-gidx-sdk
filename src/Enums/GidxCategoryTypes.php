<?php

namespace GidxSDK\Enums;

use Saritasa\Enum;

/**
 * Gidx document statuses.
 */
class GidxCategoryTypes extends Enum
{
    public const OTHER = 1;
    public const DRIVERS_LICENSE = 2;
    public const PASSPORT = 3;
    public const MILITARY_ID = 4;
    public const GOVT_ISSUED_PHOTO_ID = 5;
    public const STUDENT_PHOTO_ID = 6;
    public const UTILITY_BILL = 7;
}
