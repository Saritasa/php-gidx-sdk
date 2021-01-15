<?php

namespace GidxSDK\Http\Requests;

use GidxSDK\Dto\CreateSessionDto;
use GidxSDK\Dto\DeviceGpsDto;

/**
 * Gidx create session request.
 *
 * @property float[] $device_gps
 * @property string $type
 * @property float $amount
 */
abstract class GidxSessionRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return mixed[]
     */
    public function rules(): array
    {
        return [
            CreateSessionDto::DEVICE_GPS . '.' . DeviceGpsDto::LATITUDE => 'nullable|numeric',
            CreateSessionDto::DEVICE_GPS . '.' . DeviceGpsDto::LONGITUDE => 'nullable|numeric',
        ];
    }

    /**
     * Return attributes name.
     *
     * @return mixed[]
     */
    public function attributes(): array
    {
        return [
            CreateSessionDto::DEVICE_GPS . '.' . DeviceGpsDto::LATITUDE => 'Device GPS latitude',
            CreateSessionDto::DEVICE_GPS . '.' . DeviceGpsDto::LONGITUDE => 'Device GPS longitude',
            CreateSessionDto::AMOUNT => 'Amount',
        ];
    }

    /**
     * Returns dto for request.
     *
     * @return CreateSessionDto
     */
    public function toDto(): CreateSessionDto
    {
        $validatedData = $this->validated();
        $validatedData[CreateSessionDto::CUSTOMER_IP_ADDRESS] = $this->ip();

        $dto = new CreateSessionDto($validatedData);
        $dto->device_gps = is_array($this->device_gps) ? new DeviceGpsDto($this->device_gps) : null;

        return $dto;
    }
}
