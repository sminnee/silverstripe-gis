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
 * @package sapphire
 * @subpackage search
 */
class LatLngBoundsFilter extends BoundsFilter {
	
	public function apply(SQLQuery $query) {
		if(
			!is_array($this->value) 
			|| !array_key_exists('ne',$this->value) 
			|| !array_key_exists('sw',$this->value)
		) return false;
		
		$sw = explode(',',$this->value['sw']);
		$ne = explode(',',$this->value['ne']);
		if(count($sw) != 2 || count($ne) != 2) return false;
		
		// HACK
		$lng_sw = ($sw[0] < 0 || $sw[0] > 180) ? 180 : (float)$sw[0];
		$lat_sw = (float)$sw[1];

		$lng_nw = ($sw[0] < 0 || $sw[0] > 180) ? 180 : (float)$sw[0];
		$lat_nw = (float)$ne[1];
		
		$lng_ne = ($ne[0] < 0 || $ne[0] > 180) ? 180 : (float)$ne[0];
		$lat_ne = (float)$ne[1];
		
		$lng_se = ($ne[0] < 0 || $ne[0] > 180) ? 180 : (float)$ne[0];
		$lat_se = (float)$sw[1];
		
		
		$query = $this->applyRelation($query);
		$where = "MBRContains(
			GeomFromText('Polygon((
				$lng_sw $lat_sw,
				$lng_nw $lat_nw,
				$lng_ne $lat_ne,
				$lng_se $lat_se,
				$lng_sw $lat_sw
			))'),
			{$this->getName()}
		)";
		
		return $query->where($where);
	}
	
}
?>