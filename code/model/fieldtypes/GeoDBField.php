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
	
	function __construct($name, $srid = null) {
		$this->srid = $srid;
		
		parent::__construct($name);
	}
	
	public function isChanged() {
		return $this->isChanged;
	}
	
	public function requireField() {}
}
?>