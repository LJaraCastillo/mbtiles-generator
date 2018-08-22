<?php
/**
 * Created by HTML24 ApS.
 */

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

use HTML24\MBTilesGenerator\MBTilesGenerator;
use HTML24\MBTilesGenerator\TileSources\RemoteCachingTileSource;
use HTML24\MBTilesGenerator\Model\BoundingBox;
use HTML24\MBTilesGenerator\TileSources\BingMapsTileSource;

//$tile_source = new RemoteCachingTileSource('http://a.tiles.wmflabs.org/hikebike/{z}/{x}/{y}.png', array(1, 2, 3, 4));
//$tile_source->setAttribution('Data, imagery and map information provided by MapQuest, OpenStreetMap <http://www.openstreetmap.org/copyright> and contributors, ODbL <http://wiki.openstreetmap.org/wiki/Legal_FAQ#I_would_like_to_use_OpenStreetMap_maps._How_should_I_credit_you.#>.');

//$tile_source = new RemoteCachingTileSource('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', array("a", "b", "c"), true);
//$tile_source->setAttribution('Data, imagery and map information provided by MapQuest, OpenStreetMap <http://www.openstreetmap.org/copyright> and contributors, ODbL <http://wiki.openstreetmap.org/wiki/Legal_FAQ#I_would_like_to_use_OpenStreetMap_maps._How_should_I_credit_you.#>.');

$tile_source = new BingMapsTileSource('https://dev.virtualearth.net/REST/v1/Imagery/Metadata/AerialWithLabels/{center}?zl={zoom}&key={api_key}&uriScheme=https', null, "temuco_bing");
$tile_source->setApiKey("BING-API-KEY");

$tile_generator = new MBTilesGenerator($tile_source);

//Long1(left),Lat1(bottom),Long2(right),Lat2(top)
//Copenhagen
//$bounding_box = new BoundingBox('12.6061,55.6615,12.6264,55.6705');
//Volcan Villarica
//$bounding_box = new BoundingBox('-72.081086, -39.516546,-71.699016,-39.316223');
//Denmark
//$bounding_box = new BoundingBox('11.5089, 56.6877, 11.6644, 56.7378');
//Temuco
$bounding_box = new BoundingBox(-72.829, -38.917, -72.309, -38.642);

$tile_generator->setTileLimit(10000);
$tile_generator->setMaxZoom(18);
$tile_generator->setMinZoom(1);
$tile_generator->generate($bounding_box, 'temuco_php_aero.mbtiles');
