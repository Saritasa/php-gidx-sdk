<?php

/**
 * Created by Reliese Model.
 */

namespace GidxSDK\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class PaymentStatusTracking
 *
 * @property int $id
 * @property int $payment_request_id
 * @property int $action_by
 * @property string $action_type
 * @property string $old_status
 * @property string $status
 * @property int $gidx_session_response_id
 * @property Carbon $created_at
 *
// * @property User $user
 * @property GidxSessionResponse $gidx_session_response
 * @property PaymentRequest $payment_request
 */
class PaymentStatusTracking extends Model
{
    protected $table = 'payment_status_tracking';
    public const TABLE = 'payment_status_tracking';
    public const PAYMENT_REQUEST_ID = 'payment_request_id';
    public const ACTION_BY = 'action_by';
    public const ACTION_TYPE = 'action_type';
    public const GIDX_SESSION_RESPONSE_ID = 'gidx_session_response_id';
    public const OLD_STATUS = 'old_status';
    public const STATUS = 'status';

    protected $casts = [
        self::PAYMENT_REQUEST_ID => 'int',
        self::ACTION_BY => 'int',
        self::GIDX_SESSION_RESPONSE_ID => 'int'
    ];

    protected $fillable = [
        self::PAYMENT_REQUEST_ID,
        self::ACTION_BY,
        self::ACTION_TYPE,
        self::OLD_STATUS,
        self::STATUS,
        self::GIDX_SESSION_RESPONSE_ID,
    ];

    public function gidxSessionResponse(): BelongsTo
    {
        return $this->belongsTo(GidxSessionResponse::class);
    }

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentRequest::class);
    }
}
