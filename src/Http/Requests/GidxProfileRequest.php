<?php

namespace GidxSDK\Http\Requests;

use GidxSDK\Dto\CreateSessionDto;
use GidxSDK\Enums\GidxSessionTypes;

/**
 * Create / update GIDX / TSEVO customer profile
 */
class GidxProfileRequest extends GidxSessionRequest
{
    public function toDto(): CreateSessionDto
    {
        $dto = parent::toDto();
        $dto->type = GidxSessionTypes::PROFILE;
        return $dto;
    }
}
