<?php

/**
 * Created by Reliese Model.
 */

namespace GidxSDK\Models;

use GidxSDK\Contracts\IGidxCustomer;
use GidxSDK\Enums\PaymentRequestStatuses;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class PaymentRequest
 *
 * @property int $id
 * @property int $user_id
 * @property string $status
 * @property string $type
 * @property string $merchant_transaction_id
 * @property int $gidx_session_id
 * @property int $transaction_id
 * @property int $reversal_transaction_id
 * @property string $method_type
 * @property float $amount
 * @property string $currency
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $deleted_at
 *
 * @property GidxSession $gidxSession
 * @property Collection|PaymentStatusTracking[] statusTracking
 */
class PaymentRequest extends Model
{
    use SoftDeletes;

    public const TABLE = 'payment_requests';
    public const ID = 'id';
    public const USER_ID = 'user_id';
    public const GIDX_SESSION_ID = 'gidx_session_id';
    public const MERCHANT_TRANSACTION_ID = 'merchant_transaction_id';
    public const REVERSAL_TRANSACTION_ID = 'reversal_transaction_id';
    public const TYPE = 'type';
    public const METHOD_TYPE = 'method_type';
    public const STATUS = 'status';
    public const AMOUNT = 'amount';
    public const CURRENCY = 'currency';

    protected $table = self::TABLE;

    protected $casts = [
        self::USER_ID => 'int',
        self::GIDX_SESSION_ID => 'int',
        self::REVERSAL_TRANSACTION_ID => 'int',
        self::AMOUNT => 'float',
    ];

    protected $fillable = [
        self::USER_ID,
        self::STATUS,
        self::TYPE,
        self::METHOD_TYPE,
        self::MERCHANT_TRANSACTION_ID,
        self::GIDX_SESSION_ID,
        self::REVERSAL_TRANSACTION_ID,
        self::AMOUNT,
        self::CURRENCY,
    ];

    /** Session, to which this request belongs */
    public function gidxSession(): BelongsTo
    {
        return $this->belongsTo(GidxSession::class);
    }

    /** History of this payment request status changes */
    public function statusTracking(): HasMany
    {
        return $this->hasMany(PaymentStatusTracking::class);
    }

    /**
     * Check if user is owner of payment request.
     *
     * @param IGidxCustomer $user User object
     *
     * @return boolean
     */
    public function isOwner(IGidxCustomer $user): bool
    {
        return $user->id == $this->user_id;
    }

    /**
     * Mark payment request was failed.
     */
    public function markAsFailed(): void
    {
        $this->status = PaymentRequestStatuses::FAILED;
        $this->save();
    }
}
