<?php
/**
 * Created by HTML24 ApS.
 */

namespace HTML24\MBTilesGenerator\Model;


class BoundingBox
{
    /**
     * Longitude of left
     * @var float
     */
    protected $left;

    /**
     * Latitude of bottom
     * @var float
     */
    protected $bottom;

    /**
     * Longitude of right
     * @var float
     */
    protected $right;

    /**
     * Latitude of top
     * @var float
     */
    protected $top;

    /**
     * BoundingBox constructor.
     * @param float $left
     * @param float $bottom
     * @param float $right
     * @param float $top
     */
    public function __construct($left, $bottom, $right, $top)
    {
        $this->left = $left;
        $this->bottom = $bottom;
        $this->right = $right;
        $this->top = $top;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->left . ',' . $this->bottom . ',' . $this->right . ',' . $this->top;
    }

    /**
     * @param float $bottom
     */
    public function setBottom($bottom)
    {
        $this->bottom = $bottom;
    }

    /**
     * @return float
     */
    public function getBottom()
    {
        return $this->bottom;
    }

    /**
     * @param float $left
     */
    public function setLeft($left)
    {
        $this->left = $left;
    }

    /**
     * @return float
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * @param float $right
     */
    public function setRight($right)
    {
        $this->right = $right;
    }

    /**
     * @return float
     */
    public function getRight()
    {
        return $this->right;
    }

    /**
     * @param float $top
     */
    public function setTop($top)
    {
        $this->top = $top;
    }

    /**
     * @return float
     */
    public function getTop()
    {
        return $this->top;
    }



}