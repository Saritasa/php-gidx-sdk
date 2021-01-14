<?php

namespace GidxSDK\Dto\Response;

use Illuminate\Contracts\Support\Arrayable;
use Saritasa\Dto;

/**
 * Customer profile response from Gidx transfer object.
 */
class CustomerProfileResponseDto extends Dto implements Arrayable
{
    public const MERCHANT_CUSTOMER_ID = 'MerchantCustomerID';
    public const PROFILE_VERIFICATION_STATUS = 'ProfileVerificationStatus';
    public const LOCATION_DETAIL = 'LocationDetail';
    public const REASON_CODE = 'ReasonCodes';

    /**
     * Merchant customer ID.
     *
     * @var string
     */
    public $MerchantCustomerID;

    /**
     * Profile verification status.
     *
     * @var string
     */
    public $ProfileVerificationStatus;

    /**
     * Location detail.
     *
     * @var object
     */
    public $LocationDetail;

    /**
     * Identity confidence score.
     *
     * @var float
     */
    public $IdentityConfidenceScore;

    /**
     * Fraud confidence score.
     *
     * @var float
     */
    public $FraudConfidenceScore;

    /**
     * Reason codes.
     *
     * @var mixed[]
     */
    public $ReasonCodes;
}
