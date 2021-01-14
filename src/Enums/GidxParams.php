<?php

namespace GidxSDK\Enums;

use Saritasa\Enum;

class GidxParams extends Enum
{
    public const SESSION_ID = 'SessionID';
    public const MERCHANT_SESSION_ID = 'MerchantSessionID';
    public const MERCHANT_CUSTOMER_ID = 'MerchantCustomerID';
    public const MERCHANT_TRANSACTION_ID = 'MerchantTransactionID';
    public const MERCHANT_ID = 'MerchantID';
    public const RESPONSE_CODE = 'ResponseCode';
    public const RESPONSE_MESSAGE = 'ResponseMessage';
    public const SESSION_SCORE = 'SessionScore';
    public const CUSTOMER_IP_ADDRESS = 'CustomerIpAddress';
    public const DEVICE_GPS = 'DeviceGPS';
    public const AMOUNT = 'PaymentAmount';
    public const SERVICE_TYPE = 'ServiceType';
    public const CALLBACK_URL = 'CallbackURL';
    public const STATUS_CODE = 'StatusCode';
    public const STATUS_MESSAGE = 'StatusMessage';
}
