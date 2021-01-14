<?php

/**
 * Created by Reliese Model.
 */

namespace GidxSDK\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class GidxSession
 *
 * @property int $id
 * @property int $user_id
 * @property string $merchant_session_id
 * @property string $merchant_customer_id
 * @property string $merchant_transaction_id
 * @property string $service_type
 * @property string $ip_address
 * @property string $device_location
 * @property string $request_raw
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Collection|GidxSessionResponse[] $gidxSessionResponses
 */
class GidxSession extends Model
{
    public const TABLE = 'gidx_sessions';
    public const USER_ID = 'user_id';
    public const MERCHANT_SESSION_ID = 'merchant_session_id';
    public const MERCHANT_CUSTOMER_ID = 'merchant_customer_id';
    public const MERCHANT_TRANSACTION_ID = 'merchant_transaction_id';
    public const SERVICE_TYPE = 'service_type';
    public const IP_ADDRESS = 'ip_address';
    public const DEVICE_LOCATION = 'device_location';
    public const REQUEST_RAW = 'request_raw';

    protected $table = self::TABLE;

    protected $casts = [
        self::USER_ID => 'int'
    ];

    protected $fillable = [
        self::USER_ID,
        self::MERCHANT_SESSION_ID,
        self::MERCHANT_CUSTOMER_ID,
        self::MERCHANT_TRANSACTION_ID,
        self::SERVICE_TYPE,
        self::IP_ADDRESS,
        self::DEVICE_LOCATION,
        self::REQUEST_RAW,
    ];

    public function gidxSessionResponses(): HasMany
    {
        return $this->hasMany(GidxSessionResponse::class);
    }
}
