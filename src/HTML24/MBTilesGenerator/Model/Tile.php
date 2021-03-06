<?php
/**
 * Created by HTML24 ApS.
 */

namespace HTML24\MBTilesGenerator\Model;


class Tile
{
    /**
     * X Coordinate in the TMS Mercator
     * @var int
     */
    public $x;
    /**
     * Y Coordinate in the TMS Mercator
     * @var int
     */
    public $y;
    /**
     * @var int
     */
    public $z;

    public function __construct($x = null, $y = null, $z = null)
    {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
    }

    /**
     * @return int
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * @return int
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * @return int
     */
    public function getZ()
    {
        return $this->z;
    }

} 