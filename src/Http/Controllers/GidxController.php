<?php

namespace GidxSDK\Http\Controllers;

use GidxSDK\Enums\GidxDocumentParams;
use GidxSDK\Enums\GidxDocumentStatuses;
use GidxSDK\Enums\GidxParams;
use GidxSDK\Events\GidxWebhookReceived;
use GidxSDK\Http\Requests\Request;
use GidxSDK\Http\Requests\UploadDocumentRequest;
use GidxSDK\Services\GidxClient;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GidxController extends Controller
{
    public function handleWebhook(Request $request): Response
    {
        $payload = $request->all();

        Log::debug("Gidx callback from ip: " . $request->ip(), $payload);
        event(new GidxWebhookReceived($payload));

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    public function uploadDocument(UploadDocumentRequest $request, GidxClient $gidxClient)
    {
        $documentFile = $request->file('file');
        $user = $request->user();

        $params = [
            'file' => curl_file_create(
                $documentFile->getRealPath(),
                $documentFile->getMimeType(),
                $documentFile->getClientOriginalName()
            ),
            'json' => [
                GidxParams::MERCHANT_SESSION_ID => Str::uuid()->toString(),
                GidxParams::MERCHANT_CUSTOMER_ID => $user->getOrCreateGidxId(),
                GidxDocumentParams::CATEGORY_TYPE => (int)$request->category_type,
                GidxDocumentParams::DOCUMENT_STATUS => GidxDocumentStatuses::NOT_REVIEWED,
            ],
        ];

        $gidxClient->uploadDocument($params);
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
