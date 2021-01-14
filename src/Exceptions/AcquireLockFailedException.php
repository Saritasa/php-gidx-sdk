<?php

namespace GidxSDK\Exceptions;

use Illuminate\Contracts\Cache\LockTimeoutException;

/**
 * Thrown when server too busy and lock could not be acquired.
 */
class AcquireLockFailedException extends LockTimeoutException
{
    /**
     * AcquireLockFailedException constructor.
     */
    public function __construct()
    {
        parent::__construct(trans('Server Too Busy'), 503);
    }
}
