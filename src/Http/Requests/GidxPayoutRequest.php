<?php

namespace GidxSDK\Http\Requests;

use GidxSDK\Dto\CreateSessionDto;
use GidxSDK\Enums\GidxSessionTypes;

/**
 * HTTP Request to withdraw money from GIDX / TSEVO account to bank
 */
class GidxPayoutRequest extends GidxSessionRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            CreateSessionDto::AMOUNT => 'numeric|min:1',
        ];
    }

    public function toDto(): CreateSessionDto
    {
        $dto = parent::toDto();
        $dto->type = GidxSessionTypes::PAYOUT;
        return $dto;
    }
}
