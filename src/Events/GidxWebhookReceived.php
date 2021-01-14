<?php

namespace GidxSDK\Events;

class GidxWebhookReceived
{
    /**
     * The webhook payload.
     *
     * @var array
     */
    public $payload;

    /**
     * Create a new event instance.
     *
     * @param mixed[] $payload The webhook payload.
     * @return void
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }
}
