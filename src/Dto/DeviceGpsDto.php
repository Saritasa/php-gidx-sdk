<?php

namespace GidxSDK\Dto;

use Illuminate\Contracts\Support\Arrayable;
use Saritasa\Dto;

/**
 * Gidx device GPS data transfer object.
 */
class DeviceGpsDto extends Dto implements Arrayable
{
    public const LATITUDE = 'latitude';
    public const LONGITUDE = 'longitude';
    public const RADIUS = 'radius';
    public const ALTITUDE = 'altitude';
    public const SPEED = 'speed';
    public const DATE_TIME = 'date_time';

    /**
     * Latitude.
     *
     * @var float
     */
    public $latitude;

    /**
     * Longitude.
     *
     * @var float
     */
    public $longitude;

    /**
     * Radius.
     *
     * @var float
     */
    public $radius;

    /**
     * Altitude.
     *
     * @var float
     */
    public $altitude;

    /**
     * Speed.
     *
     * @var float
     */
    public $speed;

    /**
     * Date time.
     *
     * @var float
     */
    public $date_time;
}
