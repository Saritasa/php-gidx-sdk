<?php

namespace GidxSDK\Http\Controllers;

use GidxSDK\Enums\GidxDocumentParams;
use GidxSDK\Enums\GidxDocumentStatuses;
use GidxSDK\Enums\GidxParams;
use GidxSDK\Enums\GidxServiceTypes;
use GidxSDK\Events\GidxCustomerRegistrationCallback;
use GidxSDK\Events\GidxPaymentCallback;
use GidxSDK\Events\GidxWebhookReceived;
use GidxSDK\Http\Requests\Request;
use GidxSDK\Http\Requests\UploadDocumentRequest;
use GidxSDK\Services\GidxClient;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GidxController extends Controller
{
    /**
     * Handle Webhook callback from GIDX / TSEVO - response on successful/unsuccessful registration/payment/withdraw
     *
     * @param Request $request HTTP Request from TSEVO servers
     *
     * @return Response
     */
    public function handleWebhook(Request $request): Response
    {
        $payload = $request->all();

        Log::debug("Gidx callback from ip: " . $request->ip(), $payload);
        event(new GidxWebhookReceived($payload));

        $payload = isset($payload['result']) ? json_decode($payload['result'], true) : $payload;
        $gidxServiceType = Arr::get($payload, GidxParams::SERVICE_TYPE);

        switch ($gidxServiceType) {
            case GidxServiceTypes::PAYMENT:
                event(new GidxPaymentCallback($payload));
                break;
            case GidxServiceTypes::CUSTOMER_REGISTRATION:
                event(new GidxCustomerRegistrationCallback($payload));
                break;
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * Handle device HTTP request to upload document required by TSEVO to identify user
     *
     * @param UploadDocumentRequest $request HTTP Request from device
     * @param GidxClient $gidxClient Gidx API wrapper
     *
     * @return Response
     *
     * @throws Throwable
     */
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
