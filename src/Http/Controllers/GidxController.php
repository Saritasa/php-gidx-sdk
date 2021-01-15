<?php

namespace GidxSDK\Http\Controllers;

use GidxSDK\Events\GidxWebhookReceived;
use GidxSDK\Http\Requests\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class GidxController extends Controller
{
    public function handleWebhook(Request $request): Response
    {
        $payload = $request->all();

        Log::debug("Gidx callback from ip: " . $request->ip(), $payload);
        event(new GidxWebhookReceived($payload));

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
