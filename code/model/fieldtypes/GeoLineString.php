<?php
/**
 * GIS Polyline class.
 * 
 * @see http://dev.mysql.com/doc/refman/5.0/en/gis-class-polyline.html
 * @see http://www.opengis.org/docs/99-049.pdf
 * @see http://dev.mysql.com/doc/refman/5.0/en/gis-wkt-format.html
 * 
 * @package gis
 */
class GeoLineString extends GeoDBField implements CompositeDBField {
	
	protected static $wkt_name = 'LINESTRING';
	
	function requireField() {
		DB::requireField($this->tableName, $this->name, "linestring");
	}
	
	public function compositeDatabaseFields() {
		return array(
			$this->name => "GeoLineString"
		);
	}
	
	/**
	 * Set one or more points as an array,
	 * containing numeric arrays for each point
	 * (x/y or lng/lat).
	 *
	 * <example>
	 * array(
	 *   array(array(0,1),array(2,3),array(3,4),array(0,1))
	 * );
	 * </example>
	 * 
	 * @param array $points
	 */
	public function setAsPoints($points) {
		$wkt = '';
		$pointsWKT = array();
		foreach($points as $point) {
			$pointsWKT[] = implode(' ', $point);
		}
		$wkt = implode(',',$pointsWKT);
		$this->setAsWKT($this->stat('wkt_name') . "({$wkt})");
	}
	
	function writeToManipulation(&$manipulation) {
		if($this->hasValue()) {
			$manipulation['fields'][$this->name] = $this->WKT();
		} else {
			$manipulation['fields'][$this->name] = $this->nullValue();
		}
	}

	/**
	 * Parse WKT string into an array of points,
	 * with each point being represented as a numeric array
	 * with x/y or lng/lat.
	 * 
	 * <example>
	 *   array(0,1),array(2,3),array(3,4)
	 * </example>
	 *
	 * @return array
	 **/
 	public function getPoints() {
		$points = array();

		preg_match('/^LINESTRING\(([^\(]*)\)$/', $this->wkt, $wktMatches);
		if(!$wktMatches) return false;
		
		preg_match_all('/([0-9.\-]+ [0-9.\-]+)/', $wktMatches[1], $coords);
		foreach($coords[0] as $coord) {
			// resolve x/y for each coordinate
			$points[] = explode(' ', $coord);
		}
		
		return $points;
	}
	
	
	public function toJSON() {
		$polylineEncoder = new PolylineEncoder();
		$arr = array();
		$points = $this->getPoints();
		foreach($points as $point) {
			list($encodedPoints, $encodedLevels, $encodedLiteral) = $polylineEncoder->dpEncode($points); 
			$arr['points'] = $points;
			$arr['encoded'] = array(
				'points' =>  $encodedPoints,
				'levels' => $encodedLevels,
				'numLevels' => 18,
				'zoomFactor' => 2
			);
		}
		
		return Convert::raw2json($arr);
	}
	
	public function toXML() {
		$points = $this->getPoints();
		$xml = "<$this->Name srid=\"" . Convert::raw2att($this->srid) . "\">";
		$xml .= "<points>";
		foreach($points as $point) {
			$xml .= '<point x="' . Convert::raw2xml($point[0]) . '" y="' . Convert::raw2xml($point[1]) . '" />';
		}
		$xml .= "</points>";
		$xml .= "</$this->Name>";
		
		return $xml;
	}
	
	public function debug() {
		return $this->name . '(' . $this->wkt . ')';
	}
	
}
?>