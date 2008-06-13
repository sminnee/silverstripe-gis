<?php
/**
 * GIS Polygon class.
 * 
 * @see http://dev.mysql.com/doc/refman/5.0/en/gis-class-polygon.html
 * @see http://www.opengis.org/docs/99-049.pdf
 * @see http://dev.mysql.com/doc/refman/5.0/en/gis-wkt-format.html
 * 
 * @package gis
 */
class GeoPolygon extends GeoDBField implements CompositeDBField {
	
	/**
	 * @var string
	 */
	protected $valueWKT;
	
	function requireField() {
		DB::requireField($this->tableName, $this->name, "polygon");
	}
	
	function setValue($value, $record = null) {
		$this->value = $value;
	}
	
	function setAsWKT($wktString) {
		$this->valueWKT = $wktString;
		$this->isChanged = true;
	}
	
	function writeToManipulation(&$manipulation) {
		die("here");
		if($this->hasValue()) {
			// @todo 
		} else if($this->valueWKT) {
			$manipulation['fields'][$this->name] = "GeomFromText('" . addslashes($this->valueWKT) . "')";
		} else {
			$manipulation['fields'][$this->name] = $this->nullValue();
		}
	}
	
	function addToQuery(&$query) {
		parent::addToQuery($query);
		$query->select[] = "AsText({$this->name}) AS {$this->name}_AsText";
	}
	
	
	function debug() {
		return $this->name . '(' . $this->valueWKT . ')';
	}
	
	public function scaffoldFormField($title = null) {
		return new TextField($this->name, $title);
	}
}
?>