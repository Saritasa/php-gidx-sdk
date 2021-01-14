<?php

namespace GidxSDK\Contracts;

/**
 * Interface to implement in application user model
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 * @property int $id
 */
interface IGidxCustomer
{
    /**
     * Get or Create GIDX merchant customer ID.
     *
     * @return string
     */
    public function getOrCreateGidxId(): string;

    /**
     * Check if user has GIDX customer ID or not.
     *
     * @return boolean
     */
    public function hasGidxCustomerId(): bool;
}
