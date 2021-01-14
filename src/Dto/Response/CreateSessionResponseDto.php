<?php

namespace GidxSDK\Dto\Response;

use Illuminate\Contracts\Support\Arrayable;
use Saritasa\Dto;

/**
 * Create session response from Gidx transfer object.
 */
class CreateSessionResponseDto extends Dto implements Arrayable
{
    public const SESSION_ID = 'SessionID';
    public const SESSION_URL = 'SessionURL';

    /**
     * Internal ID.
     *
     * @var integer
     */
    public $ID;

    /**
     * Session ID.
     *
     * @var string
     */
    public $SessionID;

    /**
     * Session URL
     *
     * @var string
     */
    public $SessionURL;

    /**
     * Session date time expiration.
     *
     * @var string
     */
    public $SessionExpirationTime;

    /**
     * Session score.
     *
     * @var float
     */
    public $SessionScore;

    /**
     * Session reason code.
     *
     * @var mixed[]
     */
    public $ReasonCodes;
}
