<?php
/**
 * Created by HTML24 ApS.
 */

namespace HTML24\MBTilesGenerator\Model;

class Point
{
    /**
     * @var int|double
     */
    protected $X;

    /**
     * @var int|double
     */
    protected $Y;

    /**
     * Point constructor.
     * @param $X
     * @param $Y
     */
    public function __construct($X, $Y)
    {
        $this->X = $X;
        $this->Y = $Y;
    }

    /**
     * @return mixed
     */
    public function getX()
    {
        return $this->X;
    }

    /**
     * @param mixed $X
     */
    public function setX($X)
    {
        $this->X = $X;
    }

    /**
     * @return mixed
     */
    public function getY()
    {
        return $this->Y;
    }

    /**
     * @param mixed $Y
     */
    public function setY($Y)
    {
        $this->Y = $Y;
    }


}