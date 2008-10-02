<?php
/**
 * Base class for all geometry features.
 * 
 * @package gis
 * 
 * @see http://www.opengeospatial.org/specs/?page=specs
 * 
 * @param string $name
 * @param string $srid
 */
class GeoDBField extends DBField implements CompositeDBField {
	
	/**
	 * SRID - Spatial Reference Identifier
	 * 
	 * @see http://en.wikipedia.org/wiki/SRID
	 *
	 * @var string
	 */
	protected $srid = '';
	
	/**
	 * Stores the field value as a "Well-Known-Text" string,
	 * as opposed to the usual $value storage of DBField classes.
	 *
	 * @var string
	 */
	protected $wkt;
	
	/**
	 * Well-known text identifier of the subclass, e.g. POINT
	 * 
	 * @var string
	 */
	protected static $wkt_name;
	
	function __construct($name, $srid = null) {
		$this->srid = $srid;
		
		parent::__construct($name);
	}
	
	public function isChanged() {
		return $this->isChanged;
	}
	
	public function hasValue() {
		return ($this->wkt);
	}
	
	public function requireField() {}
	
	function addToQuery(&$query) {
		parent::addToQuery($query);
		$query->select[] = "AsText({$this->name}) AS {$this->name}_AsText";
	}
	
	public function setValue($value, $record = null) {
		// If we have an enter database record, look inside that
		// only if the column exists (and we're not dealing with a newly created instance)
		if($record && isset($record[$this->name . '_AsText'])) {
			if($record[$this->name . '_AsText']) {
				$this->setAsWKT($record[$this->name . '_AsText']);
			} else {
				$this->value = $this->nullValue();
			}
		} else if ($value instanceof GeoDBField) {
			$this->setAsWKT($value->WKT());
		} else if(self::is_valid_wkt($value)) {
			$this->setAsWKT($value);
		} else if(is_array($value) && $this->hasMethod('setAsArray')) {
			$this->setAsArray($value);
		} else {
			user_error("{$this->class}::setValue() - Bad value " . var_export($value, true), E_USER_ERROR);
		}
		
		$this->isChanged = true;
	}
	
	/**
	 * @param string $wktString
	 */
	public function setAsWKT($wktString) {
		$wktString = preg_replace("/GeomFromText\('(.*)'\)\$/i","\\1",$wktString);
		$this->wkt = $wktString;
		$this->isChanged = true;
	}
	
	/**
	 * @return string
	 */
	public function WKT() {
		return "GeomFromText('{$this->wkt}')";
	}
	
	/**
	 * @return string
	 */
	public function getSRID() {
		return $this->srid;
	}
	
	/**
	 * @param string $id
	 */
	public function setSRID($id) {
		$this->srid = $id;
	}
	
	/**
	 * Determines if the passed string is in valid "Well-known Text" format.
	 * For increased security and accuracy you should overload
	 * this method in the specific subclasses.
	 * 
	 * @param string $wktString
	 */
	public static function is_valid_wkt($wktString) {
		if(!is_string($wktString)) return false;
		return preg_match('/^(POINT|LINESTRING|LINEARRING|POLYGON|MULTIPOINT|MULTILINESTRING|MULTIPOLYGON|GEOMETRYCOLLECTION)\(.*\)$/', $wktString);
	}
}
?>