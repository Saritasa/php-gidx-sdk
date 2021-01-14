<?php

namespace GidxSDK\Enums;

use Saritasa\Enum;

/**
 * Gidx document statuses.
 */
class GidxDocumentStatuses extends Enum
{
    public const NOT_REVIEWED = 1;
    public const UNDER_REVIEW = 2;
    public const REVIEW_COMPLETE = 3;
}
