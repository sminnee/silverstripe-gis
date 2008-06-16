<?php
/**
 * Filter a {@link GeoPoint} object by a given boundary box
 * specified by two sets of coordinates (for north-east and south-west bounds).
 * 
 * Format:
 * &MyFilterName[sw]=<lng1>,<lat1>&MyFilterName[ne]=<lng2>,<lat2>
 * 
 * @see http://dev.mysql.com/doc/refman/5.0/en/relations-on-geometry-mbr.html
 * @author Ingo Schommer, Silverstripe Ltd. (<firstname>@silverstripe.com)
 *
 * @todo support for boundaries across the antimeridian (international dateline)
 * 
 * @package gis
 */
class LatLngBoundsFilter extends BoundsFilter {
	
	/**
	 * Get full set of 4 coordinates
	 * out of a northeast and southwest set of coordinates
	 * marking the boundary box.
	 *
	 * @param array $bounds
	 * @return array
	 */
	public function getFullCoordinates($bounds) {
		if(!$this->isValid($bounds)) return false;
		
		$coords = array();
		
		$sw = explode(',',$bounds['sw']);
		$ne = explode(',',$bounds['ne']);
		
		// HACK
		$coords['sw']['x'] = ($sw[0] < 0 || $sw[0] > 180) ? 180 : (float)$sw[0];
		$coords['sw']['y'] = (float)$sw[1];

		$coords['nw']['x'] = ($sw[0] < 0 || $sw[0] > 180) ? 180 : (float)$sw[0];
		$coords['nw']['y'] = (float)$ne[1];
		
		$coords['ne']['x'] = ($ne[0] < 0 || $ne[0] > 180) ? 180 : (float)$ne[0];
		$coords['ne']['y'] = (float)$ne[1];
		
		$coords['se']['x'] = ($ne[0] < 0 || $ne[0] > 180) ? 180 : (float)$ne[0];
		$coords['se']['y'] = (float)$sw[1];
		
		return $coords;
	}
	
}
?>