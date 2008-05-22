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
 * @package sapphire
 * @subpackage search
 */
class BoundsFilter extends SearchFilter {
	
	public function apply(SQLQuery $query) {
		if(
			!is_array($this->value) 
			|| !array_key_exists('ne',$this->value) 
			|| !array_key_exists('sw',$this->value)
		) return false;
		
		$sw = explode(',',$this->value['sw']);
		$ne = explode(',',$this->value['ne']);
		if(count($sw) != 2 || count($ne) != 2) return false;
		
		$x_sw = (float)$sw[0];
		$y_sw = (float)$sw[1];

		$x_nw = (float)$sw[0];
		$y_nw = (float)$ne[1];
		
		$x_ne = (float)$ne[0];
		$y_ne = (float)$ne[1];
		
		$x_se = (float)$ne[0];
		$y_se = (float)$sw[1];
		
		
		$query = $this->applyRelation($query);
		$where = "MBRContains(
			GeomFromText('Polygon((
				$x_sw $y_sw,
				$x_nw $y_nw,
				$x_ne $y_ne,
				$x_se $y_se,
				$x_sw $y_sw
			))'),
			{$this->getName()}
		)";
		
		return $query->where($where);
	}
	
}
?>