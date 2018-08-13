<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 13/08/18
 * Time: 10:42
 */

namespace HTML24\MBTilesGenerator\Util;

use HTML24\MBTilesGenerator\Model\BoundingBox;
use HTML24\MBTilesGenerator\Model\LatLng;
use HTML24\MBTilesGenerator\Model\Point;
use HTML24\MBTilesGenerator\Model\Tile;


class GlobalMercator
{
    protected $TileSize = 256;
    protected $EarthRadius = 6378137;
    protected $InitialResolution;
    protected $OriginShift;

    /**
     * GlobalMercator constructor.
     */
    public function __construct()
    {
        $this->InitialResolution = 2 * pi() * $this->EarthRadius / $this->TileSize;
        $this->OriginShift = 2 * pi() * $this->EarthRadius / 2;
    }

    /**
     * Converts given lat/lon in WGS84 Datum to XY in Spherical Mercator EPSG:900913
     * @param $lat double
     * @param $lon double
     * @return Point
     */
    public function LatLonToMeters($lat, $lon)
    {
        $X = $lon * $this->OriginShift / 180;
        $Y = log(tan((90 + $lat) * pi() / 360)) / (pi() / 180);
        $Y = $Y * $this->OriginShift / 180;
        return new Point($X, $Y);
    }

    /**
     * Converts given lat/lon in WGS84 Datum to XY in Spherical Mercator EPSG:900913
     * @param $latLng LatLng
     * @return Point
     */
    public function LatLngToMeters(LatLng $latLng)
    {
        return $this->LatLonToMeters($latLng->latitude, $latLng->longitude);
    }

    /**
     * Converts XY point from (Spherical) Web Mercator EPSG:3785 (unofficially EPSG:900913) to lat/lon in WGS84 Datum
     * @param Point $m
     * @return Point
     */
    public function MetersToLatLon(Point $m)
    {
        $X = ($m->getX() / $this->OriginShift) * 180;
        $Y = ($m->getY() / $this->OriginShift) * 180;
        $Y = 180 / pi() * (2 * atan(exp($Y * pi() / 180)) - pi() / 2);
        return new Point($X, $Y);
    }

    /**
     * Converts pixel coordinates in given zoom level of pyramid to EPSG:900913
     * @param Point $p
     * @param $zoom integer
     * @return Point
     */
    public function PixelsToMeters(Point $p, $zoom)
    {
        $res = $this->Resolution($zoom);
        $X = $p->getX() * $res - $this->OriginShift;
        $Y = $p->getY() * $res - $this->OriginShift;
        return new Point($X, $Y);
    }

    /**
     * Converts EPSG:900913 to pyramid pixel coordinates in given zoom level
     * @param Point $m
     * @param $zoom int
     * @return Point
     */
    public function MetersToPixels(Point $m, $zoom)
    {
        $res = $this->Resolution($zoom);
        $X = ($m->getX() + $this->OriginShift) / $res;
        $Y = ($m->getY() + $this->OriginShift) / $res;
        return new Point($X, $Y);
    }

    /**
     * @param Point $p
     * @return Tile
     */
    public function PixelsToTile(Point $p)
    {
        $X = ceil($p->getX() / $this->TileSize) - 1;
        $Y = ceil($p->getY() / $this->TileSize) - 1;
        return new Tile($X, $Y);
    }

    /**
     * @param Point $p
     * @param $zoom
     * @return Point
     */
    public function PixelsToRaster(Point $p, $zoom)
    {
        $mapSize = $this->TileSize << $zoom;
        return new Point($p->getX(), ($mapSize - $p->getY()));
    }

    /**
     * Returns tile for given mercator coordinates
     *
     * @param Point $m
     * @param $zoom int
     * @return Tile
     */
    public function MetersToTile(Point $m, $zoom)
    {
        $p = $this->MetersToPixels($m, $zoom);
        return $this->PixelsToTile($p);
    }

    /**
     * Returns bounds of the given tile in EPSG:900913 coordinates
     * @param Tile $t
     * @return BoundingBox
     */
    public function TileBounds(Tile $t)
    {
        $min = $this->PixelsToMeters(new Point(($t->getX() * $this->TileSize), ($t->getY() * $this->TileSize)), $t->getZ());
        $max = $this->PixelsToMeters(new Point((($t->getX() + 1) * $this->TileSize), ($t->getY() + 1) * $this->TileSize), $t->getZ());
        return new BoundingBox($min->getX(), $min->getY(), $max->getX(), $max->getY());
    }

    /**
     * @param Tile $t
     * @return LatLng
     */
    public function TileToLatLng(Tile $t)
    {
        //Get bounds corners and delta of longitudes
        $bounds = $this->TileLatLonBounds($t);
        $dLon = deg2rad($bounds->getRight() - $bounds->getLeft());
        //Transform to rads
        $lat1 = deg2rad($bounds->getBottom());
        $lat2 = deg2rad($bounds->getTop());
        $lon1 = deg2rad($bounds->getLeft());
        //Calculate mid point
        $Bx = cos($lat2) * cos($dLon);
        $By = cos($lat2) * sin($dLon);
        $lat3 = atan2(sin($lat1) + sin($lat2), sqrt((cos($lat1) + $Bx) * (cos($lat1) + $Bx) + $By * $By));
        $lon3 = $lon1 + atan2($By, cos($lat1) + $Bx);
        //Transform new point from rads to degrees
        $lat3 = rad2deg($lat3);
        $lon3 = rad2deg($lon3);
        return new LatLng($lat3, $lon3, $t->getZ());
    }

    /**
     * Returns bounds of the given tile in latitude/longitude using WGS84 datum
     * @param Tile $t
     * @return BoundingBox
     */
    public function TileLatLonBounds(Tile $t)
    {
        $bound = $this->TileBounds($t);
        $min = $this->MetersToLatLon(new Point($bound->getLeft(), $bound->getTop()));
        $max = $this->MetersToLatLon(new Point($bound->getRight(), $bound->getBottom()));
        return new BoundingBox($min->getX(), $min->getY(), $max->getX(), $max->getY());
    }

    /**
     * Resolution (meters/pixel) for given zoom level (measured at Equator)
     * @param $zoom int
     * @return float|int
     */
    public function Resolution($zoom)
    {
        return $this->InitialResolution / (pow(2, $zoom));
    }

    /**
     * @param $pixelSize double
     * @return int
     * @throws \Exception
     */
    public function ZoomForPixelSize($pixelSize)
    {
        for ($i = 0; $i < 30; $i++) {
            if ($pixelSize > $this->Resolution($i)) {
                return $i != 0 ? $i - 1 : 0;
            }
        }
        throw new \Exception("Invalid Operation");
    }

    /**
     * Switch to Google Tile representation from TMS
     * @param Tile $t
     * @param int $zoom
     * @return Tile
     */
    public function ToGoogleTile(Tile $t, $zoom)
    {
        return new Tile($t->getX(), (pow(2, $zoom) - 1) - $t->getY());
    }

    /**
     * Switch to TMS Tile representation from Google
     * @param Tile $t
     * @param int $zoom
     * @return Tile
     */
    public function ToTmsTile(Tile $t, $zoom)
    {
        return new Tile($t->getX(), (pow(2, $zoom) - 1) - $t->getY());
    }

    /**
     * Converts TMS tile coordinates to Microsoft QuadTree
     * @param $tx int
     * @param $ty int
     * @param $zoom int
     * @return string
     */
    public function QuadTree($tx, $ty, $zoom)
    {
        $quadtree = "";
        $ty = ((1 << $zoom) - 1) - $ty;
        for ($i = $zoom;
             $i >= 1;
             $i--) {
            $digit = 0;

            $mask = 1 << ($i - 1);

            if (($tx & $mask) != 0)
                $digit += 1;

            if (($ty & $mask) != 0)
                $digit += 2;

            $quadtree += $digit;
        }
        return $quadtree;
    }

    /**
     * Converts a quadtree to tile coordinates
     * @param $quadtree string
     * @param $zoom int
     * @return Tile
     */
    public function QuadTreeToTile($quadtree, $zoom)
    {
        $tx = 0;
        $ty = 0;
        for ($i = $zoom;
             $i >= 1;
             $i--) {
            $ch = $quadtree[$zoom - $i];
            $mask = 1 << ($i - 1);

            $digit = $ch - '0';

            if (($digit & 1) != 0)
                $tx += $mask;

            if (($digit & 2) != 0)
                $ty += $mask;
        }
        $ty = ((1 << $zoom) - 1) - $ty;
        return new Tile($tx, $ty);
    }

    /**
     * Converts a latitude and longitude to quadtree at the specified zoom level
     * @param Point $latLon
     * @param $zoom int
     * @return string
     */
    public function LatLonToQuadTree(Point $latLon, $zoom)
    {
        $m = $this->LatLonToMeters($latLon->getY(), $latLon->getX());
        $t = $this->MetersToTile($m, $zoom);
        return $this->QuadTree($t->getX(), $t->getY(), $zoom);
    }

    /**
     * Converts a quadtree location into a latitude/longitude bounding rectangle
     * @param $quadtree string
     * @return BoundingBox
     */
    public function QuadTreeToLatLon($quadtree)
    {
        $zoom = strlen($quadtree);
        $t = $this->QuadTreeToTile($quadtree, $zoom);
        return $this->TileLatLonBounds($t);
    }

    /**
     * Returns a list of all of the quadtree locations at a given zoom level within a latitude/longitude box
     * @param int $zoom
     * @param Point $latLonMin
     * @param Point $latLonMax
     * @return array
     */
    public function GetQuadTreeList($zoom, Point $latLonMin, Point $latLonMax)
    {
        if ($latLonMax->getY() < $latLonMin->getY() || $latLonMax->getX() < $latLonMin->getX()) {
            return null;
        }
        $mMin = $this->LatLonToMeters($latLonMin->getY(), $latLonMin->getX());
        $tmin = $this->MetersToTile($mMin, $zoom);
        $mMax = $this->LatLonToMeters($latLonMax->getY(), $latLonMax->getX());
        $tmax = $this->MetersToTile($mMax, $zoom);
        $arr = array();
        for ($ty = $tmin->getY(); $ty <= $tmax->getY(); $ty++) {
            for ($tx = $tmin->getX(); $tx <= $tmax->getX(); $tx++) {
                $quadtree = $this->QuadTree($tx, $ty, $zoom);
                array_push($arr, $quadtree);
            }
        }
        return $arr;
    }
}