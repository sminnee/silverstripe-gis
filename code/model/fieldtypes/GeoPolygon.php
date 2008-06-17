<?php
/**
 * GIS Polygon class.
 * 
 * @see http://dev.mysql.com/doc/refman/5.0/en/gis-class-polygon.html
 * @see http://www.opengis.org/docs/99-049.pdf
 * @see http://dev.mysql.com/doc/refman/5.0/en/gis-wkt-format.html
 * 
 * @todo Support for grouped inner rings (e.g. POLYGON(outerring, (innerring1, innerring2)))
 * 
 * @package gis
 */
class GeoPolygon extends GeoDBField implements CompositeDBField {
	
	protected static $wkt_name = 'POLYGON';
	
	function requireField() {
		DB::requireField($this->tableName, $this->name, "polygon");
	}
	
	/**
	 * @param array $rings see {@link setAsRings()}
	 */
	public static function from_rings($rings) {
		$obj = new GeoPolygon(null);
		$obj->setAsRings($rings);
		
		return $obj;
	}
	
	public function setAsWKT($wktString) {
		$this->wkt = $wktString;
		$this->isChanged = true;
	}

	/**
	 * Set one or more rings as an array,
	 * containing numeric arrays for each point
	 * (x/y or lng/lat).
	 *
	 * <example>
	 * array(
	 *   array(array(0,1),array(2,3),array(3,4),array(0,1)),// outer ring
	 *   array(array(0,1),array(1,1),array(2,1),array(0,1)),// first inner ring
	 * );
	 * </example>
	 * 
	 * @param array $rings
	 */
	public function setAsRings($rings) {
		$wkt = '';
		$ringsWKT = array();
		foreach($rings as $ring) {
			$points = array();
			foreach($ring as $coords) $points[] = implode(' ', $coords);
			$ringsWKT[] = implode(',', $points);
		}
		$wkt = '(' . implode('),(',$ringsWKT) . ')';
		$this->setAsWKT($this->stat('wkt_name') . "({$wkt})");
	}
	
	function writeToManipulation(&$manipulation) {
		if($this->hasValue()) {
			$manipulation['fields'][$this->name] = $this->WKT();
		} else {
			$manipulation['fields'][$this->name] = $this->nullValue();
		}
	}
	
	function addToQuery(&$query) {
		parent::addToQuery($query);
		$query->select[] = "AsText({$this->name}) AS {$this->name}_AsText";
	}
	
	/**
	 * Parse WKT string into an array of rings,
	 * with each point being represented as a numeric array
	 * with x/y or lng/lat.
	 * 
	 * <example>
	 * array(
	 *   array(array(0,1),array(2,3),array(3,4),array(0,1)),// outer ring
	 *   array(array(0,1),array(1,1),array(2,1),array(0,1)),// first inner ring (optional)
	 * );
	 * </example>
	 *
	 * @return array
	 **/
 	public function getRings() {
		$rings = array();

		preg_match('/^POLYGON\((.*)\)$/', $this->wkt, $wktMatches);
		if(!$wktMatches) return false;
		
		// get all rings
		preg_match_all('/\(([^\(]*)\)/', $wktMatches[1], $ringMatches);
		unset($ringMatches[0]); // we don't need the original string
		foreach($ringMatches[1] as $ring) {
			// get all coordinates for this ring
			preg_match_all('/([0-9.\-]+) ([0-9.\-]+)/', $ring, $coords);
			$ring = array();
			foreach($coords[0] as $coord) {
				// resolve x/y for each ring coordinate
				$ring[] = explode(' ', $coord);
			}
			$rings[] = $ring;
		}
		
		return $rings;
	}
	
	/**
	 * Determines if the passed string is in valid "Well-known Text" format.
	 *
	 * @param string $wktString
	 */<
	public static function is_valid_wkt($wktString) {
		if(!is_string($wktString)) return false;
		return preg_match('/^POLYGON\(([a-zA-Z0-9.,]*)\)$/', $wktString);
	}
	
	public function toJSON() {
		return Convert::raw2json($this->getRings());
	}
	
	public function toXML() {
		$xml = "<$this->Name srid=\"" . Convert::raw2att($this->srid) . "\">";
		$rings = $this->getRings();
		if($rings) foreach($rings as $ring) {
			$xml .= "<ring>";
			foreach($ring as $coordPair) {
				$xml .= '<point x="' . Convert::raw2xml($coordPair[0]) . '" y="' . Convert::raw2xml($coordPair[0]) . '" />';
			}
			$xml .= "</ring>";
		}
		$xml .= "</$this->Name>";
		
		return $xml;
	}
	
	public function debug() {
		return $this->name . '(' . $this->wkt . ')';
	}
	
}
?>