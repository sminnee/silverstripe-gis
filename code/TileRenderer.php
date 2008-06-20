<?php
/**
 * Renders Google Maps compatible tiles.
 *
 * <example>
 * 	class MyController extends Controller {
		public function gettile($request) {
			$tile = $request->getVar('tile');
			$renderer = new TileRenderer();
		
			// MyShape $db = array('MyPolyline' => 'Polyline')
			$shapes = DataObject::get('MyShape');
			foreach($shapes as $shape) {
				$renderer->addPolyline(
					$shape->MyPolyline->getPoints(),
					array('color' => '#FF00FF')
				);
			}
		
			header('Content-type: image/gif');
			return $renderer->renderByFilename($tile);
		}
	}
 * </example>
 * 
 * For effective clientside caching, we use Apache's mod_rewrite
 * to automatically reroute non-existing cache-files through
 * the generator script. You need to replace "relative_path_to_script"
 * with the URL/path to your script that triggers a TileRenderer instance.
 * <example>
 * RewriteEngine On
 * RewriteCond %{REQUEST_FILENAME} !-f
 * RewriteRule (.*) <relative_path_to_script>/?tile=$1 [L]
 * </example>
 * 
 * @author Ingo Schommer, Silverstripe Ltd. (<firstname> at silverstripe dot com)
 * 
 * @todo Make $categoryID optional
 * @todo Support for rendering more than one layer in different colors
 * @todo Support for rendering points
 * @todo Support for different filetypes (currently only gif)
 */
class TileRenderer extends Object {

	public $cacheBaseDir = 'cache/';
	
	public $emptyFilePath = 'gis/images/emptytile.gif';
	
	/**
	 * Stores generated files automatically in {@link $cacheBaseDir}.
	 * Caution: Doesn't remove existing files from the cache,
	 * the mod_rewrite rules will still pick them up.
	 * See header documentation on how to set up caching rewrite rules.
	 *
	 * @var boolean
	 */
	public $cacheTiles = true;
	
	public $tileSize = 256;
	
	public $categoryID;
	
	public $defaultSpec = array(
		'color' => '#FF0000'
	);
	
	public $backgroundColorHex = '#FFFFFF';
	
	/**
	 * @var int GDlib pointer
	 */
	protected $im;
	
	protected $polygons = array();
	
	protected $polylines = array();
	
	protected $points = array();
	
	protected $zoom;
	
	protected $pixelX;
	
	protected $pixelY;
	
	protected $extension = 'gif';
	
	protected $allowedExtensions = array(
		'gif',
		'png'
	);
	
	public function init() {
		$this->im = imagecreate($this->tileSize, $this->tileSize);
        $blank = $this->hexColorToIdentifier($this->backgroundColorHex);
        imagefilledrectangle($this->im, 0, 0, $this->tileSize, $this->tileSize, $blank );
	}
	
	public function addPolygon($points, $spec = null) {
		$this->polygons[] = array(
			'data' => $points,
			'spec' => array_merge($this->defaultSpec, (array)$spec)
		);
	}
	
	public function addPolyline($points, $spec = null) {
		$this->polylines[] = array(
			'data' => $points,
			'spec' => array_merge($this->defaultSpec, (array)$spec)
		);
	}
	
	public function addPoint($point, $spec = null) {
		$this->points[] = array(
			'data' => $points,
			'spec' => array_merge($this->defaultSpec, (array)$spec)
		);
	}
	
	public function renderByFilename($filename) {
		$spec = $this->parseFilename($filename);
		
		$this->categoryID = (isset($spec['categoryID'])) ? $spec['categoryID'] : null;
		$this->pixelX = $spec['pixelX'];
		$this->pixelY = $spec['pixelY'];
		$this->zoom = $spec['zoom'];
		$this->extension = $spec['extension'];
		
		if(!in_array($this->extension, $this->allowedExtensions)) {
			user_error('TileRenderer->renderByFilename() - Wrong extension', E_USER_ERROR);
		}
		
		return $this->render();
	}
	
	public function parseFilename($filename) {
		$spec = array();
		
		$regexWithCategory = '/^([\d]+)\/([\d]+)-([\d]+)-([\d]+)\.(png|gif)$/';
		$regexWithoutCategory = '/^([\d]+)-([\d]+)-([\d]+)\.(png|gif)$/';
		
		if(preg_match($regexWithCategory, $filename, $result)) {
			$RAW_relativePath = $result[0];
			$spec['categoryID'] = (int)$result[1]; // @todo Can we assume categories are always ints?
			$spec['pixelX'] = (int)$result[2];
			$spec['pixelY'] = (int)$result[3];
			$spec['zoom'] = (int)$result[4];
			$spec['extension'] = basename($result[5]);
		} elseif(preg_match($regexWithoutCategory, $filename, $result)) {
			$RAW_relativePath = $result[0];
			$spec['pixelX'] = (int)$result[1];
			$spec['pixelY'] = (int)$result[2];
			$spec['zoom'] = (int)$result[3];
			$spec['extension'] = basename($result[4]);
		} else {
			user_error('TileRenderer->renderByFilename() - Wrong format', E_USER_ERROR);
		}
		
		return $spec;
	}
	
	public function renderByBounds($pixelX, $pixelY, $zoom, $categoryID = null) {
		$this->pixelX = $pixelX;
		$this->pixelY = $pixelY;
		$this->zoom = $zoom;
		$this->categoryID = $categoryID;
		
		return $this->render();
	}
	
	protected function render() {
		$this->init();

		ob_start(); // capture the output

		if (!count($this->polygons) && !count($this->polylines) && !count($this->points)) {
		        readfile(Director::baseFolder() . '/' . $this->emptyFilePath);
		} else {
				// render all polygons
		        foreach($this->polygons as $polygon) $this->drawPolygon($polygon);
		
				// render all polylines
				foreach($this->polylines as $polyline) $this->drawPolyline($polyline);
				
				// render all points
				foreach($this->points as $point) $this->drawPoint($point);
					
				$blank = $this->hexColorToIdentifier($this->backgroundColorHex);
		        imagecolortransparent($this->im, $blank);
		        imagegif($this->im);
		        imagedestroy($this->im);
		}
		
		$imagedata = ob_get_clean();
		
		if($this->cacheTiles) {
			Filesystem::makeFolder($this->getAbsoluteCachePath());
			file_put_contents($this->getAbsoluteCachePath() . "/" . $this->getFilename(), $imagedata);
		}
		
		return $imagedata;
	}
	
	protected function getFilename() {
		return "{$this->pixelX}-{$this->pixelY}-{$this->zoom}.{$this->extension}";
	}
	
	protected function getAbsoluteCachePath() {
		if($this->categoryID) {
			return Director::baseFolder() . '/' . $this->cacheBaseDir . '/' . $this->categoryID . '/';
		} else {
			return Director::baseFolder() . '/' . $this->cacheBaseDir . '/';
		}
	}
	
	protected function drawPolygon($polygon){
		imagefilledpolygon(
			$this->im, 
			$this->lngLatToPixels($polygon['data']), 
			count($polygon['data']), 
			$this->hexColorToIdentifier($polygon['spec']['color'])
		);
	}
	
	protected function drawPolyline($polyline) {
		$pointlist = $this->lngLatToPixels($polyline['data']);
		$points = array_chunk($pointlist, 2);
		for($i=0; $i<count($points)-1; $i++) {
			imageline(
				$this->im,
				$points[$i][0],
				$points[$i][1],
				$points[$i+1][0],
				$points[$i+1][1],
				$this->hexColorToIdentifier($polyline['spec']['color'])
			);
		}
	}
	
	protected function drawPoint($point) {
		
	}
	
	protected function hexColorToIdentifier($hexColor) {
		list($colorR, $colorG, $colorB) = sscanf($hexColor, '#%2x%2x%2x');

 		return imagecolorallocate($this->im, $colorR, $colorG, $colorB);
	}
	
	protected function lngLatToPixels($lngLatPoints) {
		// points contain simple array of x,y,x,y,...
		$pointlist = array();
		$pointcount = 0;
		
		foreach($lngLatPoints as $coords) {
			$lng = $coords[0];
			$lat = $coords[1];
			$relativePixelPoint = GoogleMapUtility::toZoomedPixelCoords($lat, $lng, $this->zoom);
			$pointlist[] = $absolutePixelX = $relativePixelPoint->x - ($this->tileSize * $this->pixelX);
			$pointlist[] = $absolutePixelY = $relativePixelPoint->y - ($this->tileSize * $this->pixelY);
			$pointcount++;
		}
		
		return $pointlist;
	}
	
}
?>