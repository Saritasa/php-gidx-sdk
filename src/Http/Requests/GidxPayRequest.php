<?php

namespace GidxSDK\Http\Requests;

use GidxSDK\Dto\CreateSessionDto;
use GidxSDK\Enums\GidxSessionTypes;

/**
 * HTTP Request to deposit money to GIDX / TSEVO account
 */
class GidxPayRequest extends GidxSessionRequest
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
        $dto->type = GidxSessionTypes::PAY;
        return $dto;
    }
}
