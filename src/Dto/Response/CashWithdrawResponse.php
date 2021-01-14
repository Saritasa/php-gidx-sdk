<?php

namespace GidxSDK\Dto\Response;

/**
 * Response for create cash withdraw.
 */
class CashWithdrawResponse extends CoinsWithdrawResponse
{
    public const SESSION_RESPONSE = 'sessionResponse';

    /**
     * Gidx session response.
     *
     * @var mixed[]
     */
    public $sessionResponse;
}
