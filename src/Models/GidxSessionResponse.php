<?php

/**
 * Created by Reliese Model.
 */

namespace GidxSDK\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class GidxSessionResponse
 *
 * @property int $id
 * @property int $user_id
 * @property int $gidx_session_id
 * @property string $merchant_session_id
 * @property string $merchant_customer_id
 * @property string $merchant_transaction_id
 * @property string $service_type
 * @property int $status_code
 * @property string $status_message
 * @property float $session_score
 * @property string $response_raw
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property GidxSession $gidxSession
// * @property User $user
 */
class GidxSessionResponse extends Model
{
    public const TABLE = 'gidx_session_responses';
    public const USER_ID = 'user_id';
    public const GIDX_SESSION_ID = 'gidx_session_id';
    public const MERCHANT_SESSION_ID = 'merchant_session_id';
    public const MERCHANT_CUSTOMER_ID = 'merchant_customer_id';
    public const MERCHANT_TRANSACTION_ID = 'merchant_transaction_id';
    public const SERVICE_TYPE = 'service_type';
    public const STATUS_CODE = 'status_code';
    public const STATUS_MESSAGE = 'status_message';
    public const SESSION_SCORE = 'session_score';
    public const RESPONSE_RAW = 'response_raw';

    protected $table = self::TABLE;

    protected $casts = [
        self::USER_ID => 'int',
        self::GIDX_SESSION_ID => 'int',
        self::STATUS_CODE => 'int',
        self::SESSION_SCORE => 'float',
    ];

    protected $fillable = [
        self::USER_ID,
        self::GIDX_SESSION_ID,
        self::MERCHANT_SESSION_ID,
        self::MERCHANT_CUSTOMER_ID,
        self::MERCHANT_TRANSACTION_ID,
        self::SERVICE_TYPE,
        self::STATUS_CODE,
        self::STATUS_MESSAGE,
        self::SESSION_SCORE,
        self::RESPONSE_RAW,
    ];

    public function gidxSession(): BelongsTo
    {
        return $this->belongsTo(GidxSession::class);
    }
}
