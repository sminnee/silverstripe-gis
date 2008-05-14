<?php
/**
 * GIS Point class with zero-dimensional geometry.
 * Usually used to store a set of coordinates (latitude/longitude).
 * 
 * @see http://dev.mysql.com/doc/refman/5.0/en/gis-class-point.html
 * @see http://www.opengis.org/docs/99-049.pdf
 * @see http://dev.mysql.com/doc/refman/5.0/en/gis-wkt-format.html
 * 
 * @package gis
 */
class GeoPoint extends DBField implements CompositeDBField {
	protected $isChanged = false;
	
	public function from_x_y($x, $y) {
		$g = new GeoPoint(null);
		$g->X = $x;
		$g->Y = $y;
		return $g;
	}
	
	public function from_lat_lng($lat, $lng) {
		return self::from_x_y($lng, $lat);
	}
	
	function setValue($value, $record = null) {
		$this->isChanged = true;
		// If we have an enter database record, look inside that
		// only if the column exists (and we're not dealing with a newly created instance)
		if($record && isset($record[$this->name . '_AsText'])) {
			if(preg_match('/^POINT\(([0-9.\-]+) ([0-9.\-]+)\)$/', $record[$this->name . '_AsText'], $matches)) {
				$this->value = array(
					'x' => $matches[1],
					'y' => $matches[2],
				);

			} else {
				return false;
				//user_error("GeoPoint::setValue() - Bad value from database: $value ", E_USER_ERROR);
			}
		} else if ($value instanceof DBField) {
			$this->value = array(
				'x' => $value->X,
				'y' => $value->Y,
			);
		// Otherwise process an array
		} else if(is_array($value)){
			if(isset($value['x']) && isset($value['y'])) $this->value = $value;
			else if(isset($value[0]) && isset($value[1])) $this->value = array('x' => $value[0], 'y' => $value[1]);
			else user_error("GeoPoint::setValue() - Bad array " . var_export($value, true), E_USER_ERROR);
			
		// Otherwise parse a string
		} else if(preg_match('/^\s*([0-9.\-]+)\s*,\s*([0-9.\-]+)\s*$/', $value, $matches)) {
			$this->value = array(
				'x' => $matches[1],
				'y' => $matches[2],
			);

		} else {
			user_error("GeoPoint::setValue() - Bad value " . var_export($value, true), E_USER_ERROR);
		}
	}
	
	function setAsWKT($wktString) {
		preg_match('/POINT\((.*)\s(.*)\)/', $wktString, $matches);
		if(!$matches) return false;
		$this->value = array(
			'x' => $matches[1],
			'y' => $matches[2],
		);
		$this->isChanged = true;
	}
	
	function setX($x) {
		$this->isChanged = true;
		$this->value['x'] = $x;
	}
	function setY($y) {
		$this->isChanged = true;
		$this->value['y'] = $y;
	}
	function getX() {
		return $this->value['x'];
	}
	function getY() {
		return $this->value['y'];
	}
	
	function isChanged() {
		return $this->isChanged;
	}
	
	function requireField() {
		DB::requireField($this->tableName, $this->name, "point");
	}
	
	function writeToManipulation(&$manipulation) {
		if($this->hasValue()) {
			if(is_array($this->value)) {
				if(isset($this->value['x']) && isset($this->value['y'])) {
					$manipulation['fields'][$this->name] = "GeomFromText('POINT(" . (float)$this->value['x'] . " " . (float)$this->value['y'] . ")')";
				} else {
					user_error('GeoPoint::writeToManipulation(): Wrong format 
						(expects associative array with "x" and "y" coordinates)',
						E_USER_WARNING
					);
				}
			} else {
				$manipulation['fields'][$this->name] = "GeomFromText('" . addslashes($this->value) . "')";
			}
		} else {
			$manipulation['fields'][$this->name] = $this->nullValue();
		}
	}
	
	function addToQuery(&$query) {
		parent::addToQuery($query);
		$query->select[] = "AsText({$this->name}) AS {$this->name}_AsText";
	}
	
	function debug() {
		return $this->name . '(' . $this->value['x'] . ',' . $this->value['y'] . ')';
	}
	
	function toJSON() {
		return "{ x : \"" . Convert::raw2js($this->X) . "\", y : \"" . Convert::raw2js($this->Y) . "\"}";
	}
	function toXML() {
		return "<$this->Name x=\"" . Convert::raw2xml($this->X) . "\" y=\"" . Convert::raw2xml($this->Y) . "\" />";
	}

	public function scaffoldFormField($title = null) {
		return new GeoPointField($this->name, $title);
	}
}
?>