<?php
/**
 * Created by HTML24 ApS.
 */

namespace HTML24\MBTilesGenerator;

use HTML24\MBTilesGenerator\Core\MBTileFile;
use HTML24\MBTilesGenerator\Exception\TileNotAvailableException;
use HTML24\MBTilesGenerator\TileSources\BingMapsTileSource;
use HTML24\MBTilesGenerator\TileSources\RemoteCachingTileSource;
use HTML24\MBTilesGenerator\TileSources\TileSourceInterface;
use HTML24\MBTilesGenerator\Model\BoundingBox;
use HTML24\MBTilesGenerator\Util\Calculator;
use HTML24\MBTilesGenerator\Model\Tile;
use HTML24\MBTilesGenerator\Model\LatLng;
use HTML24\MBTilesGenerator\Util\GlobalMercator;

class MBTilesGenerator
{
    /**
     * @var TileSources\TileSourceInterface
     */
    protected $tileSource;

    /**
     * The maximum number of tiles we want in the file
     * if the bounding box and zoom needs more than this
     * the zoom will be stepped down, until we are within this limit.
     * @var int
     */
    protected $tileLimit = 2000;

    /**
     * The zoom we are aiming for
     * @var int
     */
    protected $maxZoom = 18;

    /**
     * The zoom we are aiming for
     * @var int
     */
    protected $minZoom = 0;

    /**
     * The last actual zoom accomplished
     * @var int
     */
    protected $effectiveZoom = -1;

    /**
     * How many percentage of the tiles are allowed to fail,
     * while still considering generation a success.
     *
     * @var int
     */
    protected $allowedFail = 5;


    protected $osm = false;

    /**
     * @param TileSourceInterface $tileSource
     */
    public function __construct(TileSourceInterface $tileSource)
    {
        $this->tileSource = $tileSource;
    }

    /**
     * Prepares the tiles by getting the tileSource to generate or download them.
     *
     * @param BoundingBox $boundingBox
     * @param string $destination File destination to write the mbtiles file to
     * @param string $name
     *
     * @return void
     * @throws \Exception
     */
    public function generate(BoundingBox $boundingBox, $destination, $name = 'mbtiles-generator')
    {
        if (file_exists($destination)) {
            unlink($destination);
            //throw new \Exception('Destination file already exists');
        }
        $tiles = $this->generateTileList($boundingBox);

        if ($this->tileSource instanceof RemoteCachingTileSource) {
            $this->tileSource->cache($tiles);
        } else if ($this->tileSource instanceof BingMapsTileSource) {
            $locations = $this->generateLocationList($boundingBox);
            $this->tileSource->prepareTiles($tiles, $locations);
        }
        error_log("Constructing *.mbtiles file");
        // Start constructing our file
        $mbtiles = new MBTileFile($destination);
        error_log("Writing metadata on database");
        // Set the required meta data
        $this->addMetaDataToDB($mbtiles, $boundingBox, $name);
        error_log("Storing tiles in database");
        // Add tiles to the database
        $this->addTilesToDB($mbtiles, $tiles);
    }

    /**
     * Set maximum zoom on this instance, defaults to 18.
     * @param int $zoom
     */
    public function setMaxZoom($zoom)
    {
        $this->maxZoom = $zoom;
    }

    /**
     * Set maximum zoom on this instance, defaults to 18.
     * @param int $zoom
     */
    public function setMinZoom($zoom)
    {
        $this->minZoom = $zoom;
    }

    /**
     * Set if is using TSM or OSM mercator for the tiles
     * @param $osm
     */
    public function setOsm($osm)
    {
        $this->osm = $osm;
    }

    /**
     * @return int
     */
    public function getTileLimit()
    {
        return $this->tileLimit;
    }

    /**
     * @param int $tileLimit
     */
    public function setTileLimit($tileLimit)
    {
        $this->tileLimit = $tileLimit;
    }
    
    /**
     * Sets the allowed failures
     * @param $allowedFail
     */
    public function setAllowedFail($allowedFail)
    {
        $this->allowedFail = $allowedFail;
    }

    /**
     * Returns the effective zoom we accomplished on last run.
     * @return int
     */
    public function getEffectiveZoom()
    {
        return $this->effectiveZoom;
    }

    /**
     * @param MBTileFile $mbtiles
     * @param Tile[] $tiles
     * @throws
     */
    protected function addTilesToDB(MBTileFile $mbtiles, $tiles)
    {
        $failed_tiles = 0;

        foreach ($tiles as $tile) {
            try {
                $mbtiles->addTile($tile->z, $tile->x, $tile->y, $this->tileSource->getTile($tile));
            } catch (TileNotAvailableException $e) {
                $failed_tiles++;
                continue;
            }
        }

        $failed_percentage = ceil(($failed_tiles / count($tiles)) * 100);

        if ($failed_percentage > $this->allowedFail) {
            // Too many tiles failed
            throw new \Exception($failed_percentage . '% of the tiles failed, which is more than the allowed ' . $this->allowedFail . '%');
        }
    }

    /**
     * @param MBTileFile $mbtiles
     * @param BoundingBox $boundingBox
     * @param string $name
     */
    protected function addMetaDataToDB(MBTileFile $mbtiles, BoundingBox $boundingBox, $name)
    {
        $mbtiles->addMeta('name', $name);
        $mbtiles->addMeta('type', 'baselayer');
        $mbtiles->addMeta('version', '1');
        $mbtiles->addMeta('format', $this->tileSource->getFormat());
        $mbtiles->addMeta('attribution', $this->tileSource->getAttribution());

        $mbtiles->addMeta('bounds', (string)$boundingBox);
        $mbtiles->addMeta('minzoom', $this->minZoom);
        $mbtiles->addMeta('maxzoom', $this->effectiveZoom);
    }

    /**
     * @param BoundingBox $boundingBox
     * @return Tile[]
     * @throws \Exception
     */
    protected function generateTileList(BoundingBox $boundingBox)
    {
        $tiles = array();
        if ($this->minZoom <= $this->maxZoom) {
            for ($i = $this->minZoom; $i <= $this->maxZoom; $i++) {
                $zoom_tiles = $this->generateTileListForZoom($boundingBox, $i);
                if (count($tiles) + count($zoom_tiles) < $this->tileLimit) {
                    $tiles = array_merge($tiles, $zoom_tiles);
                    $this->effectiveZoom = $i;
                } else {
                    // We got to many tiles, so no more zoom levels.
                    break;
                }
            }
        } else {
            throw new \Exception('MinZoom should be less or equals to MaxZoom');
        }
        return $tiles;
    }

    /**
     * @param BoundingBox $boundingBox
     * @return LatLng[]
     * @throws \Exception
     */
    protected function generateLocationList(BoundingBox $boundingBox)
    {
        $locations = array();
        if ($this->minZoom <= $this->maxZoom) {
            for ($zoom = $this->minZoom; $zoom <= $this->maxZoom; $zoom++) {
                $zoom_locations = $this->generateLocationListForZoom($boundingBox, $zoom);
                if (count($locations) + count($zoom_locations) < $this->tileLimit) {
                    $locations = array_merge($locations, $zoom_locations);
                } else {
                    // We got to many tiles, so no more zoom levels.
                    break;
                }
            }
        } else {
            throw new \Exception('MinZoom should be less or equals to MaxZoom');
        }
        return $locations;
    }

    /**
     * @param BoundingBox $boundingBox
     * @param int $zoom
     * @return Tile[]
     */
    protected function generateTileListForZoom(BoundingBox $boundingBox, $zoom)
    {
        $tiles = array();
        $start_tile = $this->coordinatesToTile(
            $boundingBox->getLeft(),
            $boundingBox->getBottom(),
            $zoom
        );
        $end_tile = $this->coordinatesToTile(
            $boundingBox->getRight(),
            $boundingBox->getTop(),
            $zoom
        );
        for ($x = $start_tile->x; $x <= $end_tile->x; $x++) {
            for ($y = $start_tile->y; $y <= $end_tile->y; $y++) {
                $correctedY = $y;
                if ($this->tileSource->getOsm()) {
                    $correctedY = Calculator::flipYTmsToOsm($y, $zoom);
                }
                $tiles[] = new Tile($x, $correctedY, $zoom);
            }
        }
        return $tiles;
    }

    /**
     * @param BoundingBox $boundingBox
     * @param int $zoom
     * @return LatLng[]
     */
    protected function generateLocationListForZoom(BoundingBox $boundingBox, $zoom)
    {
        $locations = array();
        $start_tile = $this->coordinatesToTile(
            $boundingBox->getLeft(),
            $boundingBox->getBottom(),
            $zoom
        );
        $end_tile = $this->coordinatesToTile(
            $boundingBox->getRight(),
            $boundingBox->getTop(),
            $zoom
        );
        $globalMercator = new GlobalMercator();
        for ($x = $start_tile->x; $x <= $end_tile->x; $x++) {
            for ($y = $start_tile->y; $y <= $end_tile->y; $y++) {
                $latLng = $globalMercator->TileToLatLng(new Tile($x, $y, $zoom));
                $locations[] = $latLng;
            }
        }
        return $locations;
    }

    /**
     * @param float $longitude
     * @param float $latitude
     * @param int $zoom
     * @return Tile
     */
    protected function coordinatesToTile($longitude, $latitude, $zoom)
    {
        $tile = new Tile();
        $tile->z = $zoom;
        $tile->x = Calculator::longitudeToX($longitude, $zoom);
        $tile->y = Calculator::latitudeToY($latitude, $zoom);
        return $tile;
    }
}