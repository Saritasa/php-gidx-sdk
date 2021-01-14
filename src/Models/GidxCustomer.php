<?php

namespace GidxSDK\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Trait to use in application User model
 *
 * @property string $merchant_customer_id
 *
 * @mixin Model
 */
trait GidxCustomer
{
    /**
     * Get or Create GIDX merchant customer ID.
     *
     * @return string
     */
    public function getOrCreateGidxId(): string
    {
        if (!$this->merchant_customer_id) {
            $this->merchant_customer_id = Str::uuid()->toString();
            $this->save();
        }

        return $this->merchant_customer_id;
    }

    /**
     * Check user has GIDX customer ID or not.
     *
     * @return boolean
     */
    public function hasGidxCustomerId(): bool
    {
        return !!$this->merchant_customer_id;
    }
}
