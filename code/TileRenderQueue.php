<?php
require_once('../gis/thirdparty/GoogleMapUtility.php');

/**
 * Symbolizes a single tile which is queued up for processing.
 * Uses the tile URL as a unique identifier to check if the
 * tile is already queued up for processing, and avoids double
 * additions in the create_*() methods.
 * 
 * @uses GoogleMapTile
 * 
 * @package gis
 */
class TileRenderQueue extends DataObject {
	
	static $db = array(
		'URL' => "Text",
		'Status' => "Enum('Open,Processing','Open')",
	);
	
	static $indexes = array(
		'URL' => true,
		'Status' => true,
	);
	
	/**
	 * Uses {@link GoogleMapUtility} to convert a bounday box in WGS84 coordinates
	 * to the tiles covered by this boundary. Stores those tiles as a unique
	 * reference in the database as new {@link TileRenderQueue} objects who
	 * can be rendered by the {@link TileRenderQueue_Controller}.
	 * Note: This method just queues requests, doesn't render tiles itself.
	 * 
	 * In case you're also generating tiles "on the fly" by browser requests,
	 * make sure to use the same {@link TileRenderer} instance or conventions
	 * in the {@link $renderer} parameter. Otherwise you might cache tiles
	 * at a different location, or with different extensions.
	 * 
	 * Example class MyShape
	 * <example>
	 * class MyShape extends DataObject {
	 * static $db = array(
	 * 	'MyPolygon' => 'Polygon'
	 * );
	 * }
	 * </example>
	 * <example>
	 * function populateQueue() {
	 * 	$query = singleton('MyShape')->extendedSQL();
	 * 	$query->select = array(
	 * 		'`MyShape`.`ID`',
	 * 		'ASTEXT(ENVELOPE(`MyShape`.`MyPolygon`)) AS MyPolygonMBR'
	 * 	);
	 * 	$shapes = $query->execute();
	 * 	
	 * 	foreach($shapes as $shape) {
	 * 		$poly = DBField::create('GeoPolygon', $shape['MyPolygonMBR']);
	 * 		
	 * 		// first and second-last coordinates of the ring can be assumed
	 * 		// to be the min and max coordinates in this specific geopoly
	 * 		$rings = $poly->getRings();
	 * 		$mbrArr = $rings[0];
	 * 		$lat = $mbrArr[0][1];
	 * 		$lng = $mbrArr[0][0];
	 * 		$maxLat = $mbrArr[2][1];
	 * 		$maxLng = $mbrArr[2][0];
	 * 
	 * 		// add all necessary tiles for this network and bounds to the queue
	 * 		TileRenderQueue::create_by_boundary($renderer, $lat, $lng, $maxLat, $maxLng);
	 * 		unset($poly);
	 * 	}
	 * }
	 * </example>
	 * 
	 * @param TileRenderer $renderer Used to determine the URL format and folder structure
	 * @param float $lat
	 * @param float $lng
	 * @param float $maxLat
	 * @param float $maxLng
	 * @param int $minZoom
	 * @param int $maxZoom 
	 * @return array Numeric array of tile urls relative to the cache folder, e.g. array('42/34234-23423-17.gif','42/34234-23424-17.gif');
	 */
	static function create_by_boundary($renderer, $lat, $lng, $maxLat, $maxLng, $minZoom = 1, $maxZoom = 18) {
		$urls = array();
		
		// for each zoom level
		foreach(range($minZoom, $maxZoom) as $zoom) {
			$minPointMeters = GoogleMapUtility::latLonToMeters($lat, $lng);
			$minPointTile = GoogleMapUtility::metersToTile($minPointMeters->x, $minPointMeters->y, $zoom);
			$maxPointMeters = GoogleMapUtility::latLonToMeters($maxLat, $maxLng);
			$maxPointTile = GoogleMapUtility::metersToTile($maxPointMeters->x, $maxPointMeters->y, $zoom);
			// for each tile on y direction
			foreach(range($minPointTile->y, $maxPointTile->y) as $ty) {
				// for each tile in x direction
				foreach(range($minPointTile->x, $maxPointTile->x) as $tx) {
					$googleTile = GoogleMapTile::create_from_tms_tile($tx, $ty, $zoom);
					$tileUrl = $renderer->getRelativeTilePath();
					$tileUrl .= $renderer->getFilename(
						$googleTile->x, 
						$googleTile->y, 
						$googleTile->zoom
					);
					$urls[] = $tileUrl;
				}
			}
		}
		if($urls) foreach($urls as $url) {
			if(!self::has_pending_tile($url)) {
				$item = new TileRenderQueue();
				$item->URL = $url;
				$item->write();
				unset($item);
			}
		}
		
		return $urls;
	}
	
	/**
	 * Determines if the queue already has this tile queued up.
	 * 
	 * @return boolean
	 */
	public static function has_pending_tile($url) {
		return (bool)DB::query("
			SELECT COUNT(*) 
			FROM `TileRenderQueue`
			WHERE `URL` = '{$url}'
		")->value();
	}
}

/**
 * @package gis
 */
class TileRenderQueue_Controller extends Controller {
	
	function init() {
		parent::init();
		
		if(!Permission::check('ADMIN')) {
			return Security::permissionFailure($this);
		}
	}
	
	/**
	 * @param string $renderBaseURL
	 */
	protected $renderBaseURL = "SupplyShape/generatetile/?tile=%s&renderonly=1";
	
	function index() {
		return <<<HTML
		<h1>Tile Render Queue Admin</h1>
		<ul>
			<li><a href="status">Status</a></li>
			<li><a href="process">Process next tile in the queue</a></li>
			<li><a href="clear">Remove all queue items</a></li>
		</ul>
HTML;
	}
	
	function status() {
		$openCount = DB::query("SELECT COUNT(*) FROM `TileRenderQueue` WHERE Status = 'Open'")->value();
		$processingCount = DB::query("SELECT COUNT(*) FROM `TileRenderQueue` WHERE Status = 'Processing'")->value();
		return <<<HTML
		<h1>Queue Status</h1>
		<ul>
			<li>Open: $openCount</li>
			<li>Currently processing: $processingCount</li>
		</ul>
HTML;
	}
	
	function clear() {
		$openCount = DB::query("SELECT COUNT(*) FROM `TileRenderQueue` WHERE Status = 'Open'")->value();
		DB::query("DELETE FROM `TileRenderQueue` WHERE Status = 'Open'");
		Debug::message("Removed {$openCount} queued tiles");
	}
	
	/**
	 * Render one or more 
	 */
	function process() {
		$maxCount = (isset($_REQUEST['maxcount'])) ? (int)$_REQUEST['maxcount'] : 1;
		$queueItems = DataObject::get(
			'TileRenderQueue',
			null, // filter,
			"Created ASC", // sort
			null, // join
			$maxCount
		);
		if(!$queueItems) {
			Debug::message("Nothing to process");
			return false;
		}
		
		foreach($queueItems as $queueItem) {
			$queueItem->Status = 'Processing';
			$queueItem->write();
			Debug::message("Processing tile '{$queueItem->URL}'");
			$response = Director::test(sprintf($this->renderBaseURL, $queueItem->URL));
			var_dump($response->getStatusCode());
			if($response->getStatusCode() > 200) {
				Debug::message("Error processing tile '{$queueItem->URL}");
			} else {
				$queueItem->delete();
				unset($queueItem);
			}
		}
	}
	
	/**
	 * Run the process continuosly as a daemon instead of
	 * triggering manually through URL or a cronjob.
	 * Uses the sleep() command to keep running without
	 * timing out. Quits itself once it reaches a certain memory
	 * threshold, in which case the process should be automatically
	 * respawn from the system daemon monitoring it.
	 * Make sure that no single tile rendering call goes above
	 * the memory limit, or you will end up with endlessly respawning
	 * unfinished processes.
	 * 
	 * @see http://doc.silverstripe.com/doku.php?id=sake
	 */
	function rundaemon() {
		$memLimitBytes = 1024*1024*1024; // 1GB
		$timeoutSeconds = 300;
		set_time_limit(0);
		while(memory_get_usage() < $memLimitBytes) {
			if($this->hasPendingQueue()) {
				$this->process();
				sleep(1);
			} else {
				sleep($timeoutSeconds);
			}
		}
	}
	
	/**
	 * @return boolean
	 */
	protected function hasPendingQueue() {
		return (DB::query("SELECT COUNT(*) FROM `TileRenderQueue` WHERE `Status` = 'Open'")->value() > 0);
	}
	
}
?>