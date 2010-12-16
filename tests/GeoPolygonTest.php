<?php
/**
 * @package gis
 * @subpackage tests
 */
class GeoPolygonTest extends SapphireTest {
	
	protected $extraDataObjects = array(
		'GeoPolygonTest_Obj',
	);

	protected $outerRingArr = array(
		array(0, 0),
		array(1, 4),
		array(4, 2),
		array(0, 0),
	);

	protected $innerRingArr = array(
		array(1, 1),
		array(2, 3),
		array(3, 2),
		array(1, 1),
	);
	
	// gives you a triangle with a hole in the middle (two rings)
	protected $testWKTString = 'POLYGON((0 0,1 4,4 2,0 0),(1 1,2 3,3 2,1 1))';

	function testReadFromDatabase() {
		// Let's insert some test data directly into the database
		DB::query("INSERT INTO GeoPolygonTest_Obj SET ID = 1, Polygon = GeomFromText('$this->testWKTString')");
		
		$obj = DataObject::get_by_id("GeoPolygonTest_Obj", 1);

		$rings = $obj->Polygon->getRings();
		$this->assertEquals(2, count($rings));

		// test first ring coordinates
		$this->assertEquals($this->outerRingArr, $rings[0]);
		
		// test second ring coordinates
		$this->assertEquals($this->innerRingArr, $rings[1]);
	}
	
	function testWriteToDatabase() {
		$obj = new GeoPolygonTest_Obj();
		$obj->Polygon = GeoPolygon::from_rings(array($this->outerRingArr,$this->innerRingArr));
		$obj->write();

		// Test that the geo-data was saved properly
		$this->assertEquals($this->testWKTString, DB::query("SELECT AsText(Polygon) FROM GeoPolygonTest_Obj WHERE ID = $obj->ID")->value());
	}
	
}

class GeoPolygonTest_Obj extends DataObject implements TestOnly {
	static $db = array(
		'Polygon' => 'GeoPolygon', 
	);
}
?>