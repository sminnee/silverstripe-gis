<?php
/**
 * Filter a {@link GeoPoint} object by a given boundary box
 * specified by two sets of coordinates (for north-east and south-west bounds).
 * 
 * Format:
 * &MyFilterName[sw]=<x1>,<y1>&MyFilterName[ne]=<x2>,<y2>
 * 
 * @todo Filter for more than four points (=true polygons)
 * 
 * @author Ingo Schommer, Silverstripe Ltd. (<firstname>@silverstripe.com)
 *
 * @package gis
 */
class BoundsFilter extends SearchFilter {
	
	public function apply(SQLQuery $query) {
		$coords = $this->getFullCoordinates($this->value);
		if(!$coords) return false;
		
		$query = $this->applyRelation($query);
		$where = "MBRContains(
			GeomFromText('Polygon((
				{$coords['sw']['x']} {$coords['sw']['y']},
				{$coords['nw']['x']} {$coords['nw']['y']},
				{$coords['ne']['x']} {$coords['ne']['y']},
				{$coords['sw']['x']} {$coords['sw']['y']}
			))'),
			{$this->getName()}
		)";
		
		return $query->where($where);
	}
	
	/**
	 * Checks if the boundary value array is valid.
	 * Does it have a a northeast and southwest coordinate marking the box?
	 * Does it have two commaseparated
	 *
	 * @param array $bounds
	 * @return boolean
	 */
	public function isValid($bounds) {
		if(
			!is_array($bounds) 
			|| !array_key_exists('ne',$bounds) 
			|| !array_key_exists('sw',$bounds)
		) return false;
		
		$sw = explode(',',$bounds['sw']);
		$ne = explode(',',$bounds['ne']);
		if(count($sw) != 2 || count($ne) != 2) return false;
		
		return true;
	}
	
	/**
	 * Get full set of 4 coordinates
	 * out of a northeast and southwest set of coordinates
	 * marking the boundary box.
	 *
	 * @param array$bounds
	 * @return array
	 */
	public function getFullCoordinates($bounds) {
		if(!$this->isValid($bounds)) return false;
		
		$coords = array();
		
		$sw = explode(',',$bounds['sw']);
		$ne = explode(',',$bounds['ne']);
		
		$coords['sw']['x'] = (float)$sw[0];
		$coords['sw']['y'] = (float)$sw[1];

		$coords['nw']['x'] = (float)$sw[0];
		$coords['nw']['y'] = (float)$ne[1];
		
		$coords['ne']['x'] = (float)$ne[0];
		$coords['ne']['y'] = (float)$ne[1];
		
		$coords['se']['x'] = (float)$ne[0];
		$coords['se']['y'] = (float)$sw[1];
		
		return $coords;
	}
	
}
?>