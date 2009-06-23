<?php
/**
 * @package gis
 */
class GoogleMapUtility {
    static $TILE_SIZE = 256;

    public static function fromXYToLatLng($point,$zoom) {
        $scale = (1 << ($zoom)) * GoogleMapUtility::$TILE_SIZE;
        
        return new GoogleMapPoint(
            (int) ($normalised->x * $scale),
            (int)($normalised->y * $scale)
        );
    
        return new GoogleMapPoint(
            $pixelCoords->x % GoogleMapUtility::$TILE_SIZE, 
            $pixelCoords->y % GoogleMapUtility::$TILE_SIZE
        );
    }
    
    public static function fromMercatorCoords($point) {
             $point->x *= 360; 
             $point->y = rad2deg(atan(sinh($point->y))*M_PI);
        return $point;
    }
    
    public static function getPixelOffsetInTile($lat,$lng,$zoom) {
        $pixelCoords = GoogleMapUtility::toZoomedPixelCoords($lat, $lng, $zoom);
        return new GoogleMapPoint(
            $pixelCoords->x % GoogleMapUtility::$TILE_SIZE, 
            $pixelCoords->y % GoogleMapUtility::$TILE_SIZE
        );
    }

    public static function getTileRect($x,$y,$zoom) {
            $tilesAtThisZoom = 1 << $zoom;
        $lngWidth = 360.0 / $tilesAtThisZoom;
        $lng = -180 + ($x * $lngWidth);

        $latHeightMerc = 1.0 / $tilesAtThisZoom;
        $topLatMerc = $y * $latHeightMerc;
        $bottomLatMerc = $topLatMerc + $latHeightMerc;

        $bottomLat = (180 / M_PI) * ((2 * atan(exp(M_PI * 
            (1 - (2 * $bottomLatMerc))))) - (M_PI / 2));
        $topLat = (180 / M_PI) * ((2 * atan(exp(M_PI * 
            (1 - (2 * $topLatMerc))))) - (M_PI / 2));

        $latHeight = $topLat - $bottomLat;

        return new GoogleMapBoundary($lng, $bottomLat, $lngWidth, $latHeight);
    }

    public static function toMercatorCoords($lat, $lng) {
        if ($lng > 180) {
            $lng -= 360;
        }

        $lng /= 360;
        $lat = asinh(tan(deg2rad($lat)))/M_PI/2;
        return new GoogleMapPoint($lng, $lat);
    }

    public static function toNormalisedMercatorCoords($point) {
        $point->x += 0.5;
        $point->y = abs($point->y-0.5);
        return $point;
    }

    public static function toTileXY($lat, $lng, $zoom) {
        $normalised = GoogleMapUtility::toNormalisedMercatorCoords(
            GoogleMapUtility::toMercatorCoords($lat, $lng)
        );
        $scale = 1 << ($zoom);
        return new GoogleMapPoint((int)($normalised->x * $scale), (int)($normalised->y * $scale));
    }

    public static function toZoomedPixelCoords($lat, $lng, $zoom) {
        $normalised = GoogleMapUtility::toNormalisedMercatorCoords(
            GoogleMapUtility::toMercatorCoords($lat, $lng)
        );
        $scale = (1 << ($zoom)) * GoogleMapUtility::$TILE_SIZE;
        return new GoogleMapPoint(
            (int) ($normalised->x * $scale), 
            (int)($normalised->y * $scale)
        );
    }

	static function originShift() {
		return 2 * pi() * 6378137 / 2;
	}
	
	static function initialResolution() {
		return 2 * pi() * 6378137 / self::$TILE_SIZE;
	}

	static function latLonToMeters($lat, $lng) {
		$mx = $lng * self::originShift() / 180;
		$my = log( tan((90 + $lat) * pi() / 360.0 )) / (pi() / 180);
		$my = $my * self::originShift() / 180;
		return new GoogleMapPoint($mx, $my);
	}
	
	static function metersToLatLon($mx, $my) {
		$lng = ($mx / self::originShift()) * 180.0;
		$lat = ($my / self::originShift()) * 180.0;

		$lat = 180 / pi() * (2 * atan( exp( $lat * pi() / 180.0)) - pi() / 2.0);
		return new GoogleMapPoint($lat, $lng);
	}
	
	static function metersToTile($mx, $my, $zoom) {
		$p = self::metersToPixels($mx, $my, $zoom);
		return self::pixelsToTile($p->x, $p->y);
	}
	
	static function metersToPixels($mx, $my, $zoom) {
		$res = self::resolution($zoom);
		$px = ($mx + self::originShift()) / $res;
		$py = ($my + self::originShift()) / $res;
		return new GoogleMapPoint($px, $py);
	}
	
	static function pixelsToMeters($px, $py, $zoom) {
		$res = self::resolution($zoom);
		$mx = $px * $res - self::originShift();
		$my = $py * $res - self::originShift();
		return new GoogleMapPoint($mx, $my);
	}
	
	static function pixelsToTile($px, $py) {
		$tx = (int)ceil( $px / (float)self::$TILE_SIZE ) - 1;
		$ty = (int)ceil( $py / (float)self::$TILE_SIZE ) - 1;
		return new GoogleMapPoint($tx, $ty);
	}
	
	static function resolution($zoom) {
		return self::initialResolution() / pow(2, $zoom);
	}
}

/**
 * @package gis
 */
class GoogleMapPoint {
     public $x,$y;
     function __construct($x,$y) {
          $this->x = $x;
          $this->y = $y;
     }

     function __toString() {
          return "({$this->x},{$this->y})";
     }
}

/**
 * @package gis
 */
class GoogleMapBoundary {
     public $x,$y,$width,$height;
     function __construct($x,$y,$width,$height) {
          $this->x = $x;
          $this->y = $y;
          $this->width = $width;
          $this->height = $height;
     }

	function x1() {
		return $this->x;
	}
	
	function y1() {
		return $this->y;
	}
	
	function x2() {
		return $this->x + $this->width;
	}
	
	function y2() {
		return $this->y + $this->height;
	}
     function __toString() {
          return "({$this->x},{$this->y},{$this->width},{$this->height})";
     }
}

/**
 * @package gis
 */
class GoogleMapTile {
	
	public static $minZoom = 1;

	public static $maxZoom = 18;
	
	public $x;
	
	public $y;
	
	public $zoom;
	
	function __construct($x,$y, $zoom) {
		$this->x = $x;
		$this->y = $y;
		$this->zoom = $zoom;
     }

	static function create_from_tms_tile($tx, $ty, $zoom) {
		return new GoogleMapTile($tx, ((pow(2, $zoom ) -1) - $ty), $zoom);
	}

}

?>