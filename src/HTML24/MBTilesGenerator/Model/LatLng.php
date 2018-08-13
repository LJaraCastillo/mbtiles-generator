<?php
/**
 * Created by HTML24 ApS.
 */

namespace HTML24\MBTilesGenerator\Model;


class LatLng
{
    /**
     * latitude
     * @var double
     */
    public $latitude;
    /**
     * longitude
     * @var double
     */
    public $longitude;
    /**
     * @var int
     */
    public $z;

    public function __construct($latitude = null, $longitude = null, $z = null)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->z = $z;
    }


} 