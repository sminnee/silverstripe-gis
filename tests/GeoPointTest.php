<?php
/**
 * @package gis
 */
class GeoPointTest extends SapphireTest {
	
	static $fixture_file = false;

	function testSettingMultiValueDBFieldViaObject() {
		/* When you set a field to a DBField object, the name should be set automatically. */
		$pointObj = new GeoPointTest_Obj();
		$newPoint = GeoPoint::from_x_y(2,3);
		$pointObj->Point = $newPoint;
		$this->assertEquals("Point", $newPoint->Name);
		$this->assertEquals("Point", $pointObj->Point->Name);
		
		/* After the Point field of the DataObject is set, it $pointObj->Point should point to the actual object that you passed */
		$this->assertSame($newPoint, $pointObj->Point);
		
		/* The obj() and dbObject() methods should return this object, too */
		$this->assertSame($newPoint, $pointObj->obj('Point'));
		$this->assertSame($newPoint, $pointObj->dbObject('Point'));
	}

	function testSettingByAccessingSubParameters() {
		/* If you set a parameter of a DataObject's value object, then a new DBField object will be created for you. */
		$pointObj = new GeoPointTest_Obj();
		$pointObj->Point->X = 2;
		$pointObj->Point->Y = 3;
		
		$this->assertEquals("Point", $pointObj->Point->Name);
		$this->assertEquals("GeoPoint", $pointObj->Point->class);
		$this->assertEquals(2, $pointObj->Point->X);
		$this->assertEquals(3, $pointObj->Point->Y);
	}
	
	function testHasField() {
		/* $obj->hasField($field) should return true on newly created objects, for all applicable fields */
		$pointObj = new GeoPointTest_Obj();
		$this->assertTrue($pointObj->hasField("Title"));
		$this->assertTrue($pointObj->hasField("Point"));
		$this->assertFalse($pointObj->hasField("XJDKLS"));
		
		/* This should also be the case on object queried from the database */
		$pointObj->write();
		$otherObj = DataObject::get_by_id("GeoPointTest_Obj", $pointObj->ID);
		$this->assertTrue($otherObj->hasField("Title"));
		$this->assertTrue($otherObj->hasField("Point"));
		$this->assertFalse($otherObj->hasField("XJDKLS"));
	}
	
	function testWriteToDatabase() {
		$pointObj = new GeoPointTest_Obj();

		$pointObj->Title = "Test item";
		$pointObj->Point = GeoPoint::from_x_y(2,3);
		$pointObj->write();

		// Test that the geo-data was saved properly
		$this->assertEquals("POINT(2 3)", DB::query("SELECT AsText(Point) FROM GeoPointTest_Obj WHERE ID = $pointObj->ID")->value());
	}
	
	function testReadFromDatabase() {
		/* Let's insert some test data directly into the database */
		DB::query("INSERT INTO GeoPointTest_Obj SET ID = 1, Point = GeomFromText('POINT(1 5)')");
		
		$pointObj = DataObject::get_by_id("GeoPointTest_Obj", 1);
		$this->assertEquals(1, $pointObj->Point->X);
		$this->assertEquals(5, $pointObj->Point->Y);
	}
	
	function testSubclassWriteToDatabase() {
		/* Test writing to database for GeoPoints on defined on subclass DataObjects */
		$pointObj = new GeoPointTest_ChildObj2();

		$pointObj->Title = "Test item";
		$pointObj->Point = GeoPoint::from_x_y(2,3);
		$pointObj->write();

		// Test that the geo-data was saved properly
		$this->assertEquals("POINT(2 3)", DB::query("SELECT AsText(Point) FROM GeoPointTest_ChildObj2 WHERE ID = $pointObj->ID")->value());
	}
	
	function testSubclassReadFromDatabase() {
		/* Test reading from database for GeoPoints on defined on subclass DataObjects */
		DB::query("INSERT INTO GeoPointTest_BaseObj2 SET ClassName = 'GeoPointTest_ChildObj2', ID = 1");
		DB::query("INSERT INTO GeoPointTest_ChildObj2 SET ID = 1, Point = GeomFromText('POINT(1 5)')");
		
		/* If you request the child object itself, then the point should be selected */
		$pointObj = DataObject::get_by_id("GeoPointTest_ChildObj2", 1);
		$this->assertEquals(1, $pointObj->Point->X);
		$this->assertEquals(5, $pointObj->Point->Y);

		/* Additionally, when requesting the parent object, the point should be selected */
		$pointObj = DataObject::get_by_id("GeoPointTest_BaseObj2", 1);
		$this->assertEquals(1, $pointObj->Point->X);
		$this->assertEquals(5, $pointObj->Point->Y);
	}
	
	function testEditedValue() {
		/* A point that is edited needs to be re-written to the database on write.  We first write... */
		$pointObj = new GeoPointTest_Obj();
		$pointObj->Point->X = 1;
		$pointObj->Point->Y = 2;
		$pointObj->write();
		
		/* ..and then update... */
		$pointObj->Point->X = 3;
		$pointObj->Point->Y = 4;
		$pointObj->write();
		
		/* ...and the database should contain the new data */
		$this->assertEquals("POINT(3 4)", DB::query("SELECT AsText(Point) FROM GeoPointTest_Obj WHERE ID = $pointObj->ID")->value());
	}
	
	function testSetValue() {
		$pointObj = new GeoPointTest_Obj();

		/* We can set a point object to an 2-element array. */
		$pointObj->Point->setValue(array('1', '2'));
		$this->assertEquals(1, $pointObj->Point->X);
		$this->assertEquals(2, $pointObj->Point->Y);

		/* Or an map containing x & y. */
		$pointObj->Point->setValue(array('x' => 3, 'y' => 4));
		$this->assertEquals(3, $pointObj->Point->X);
		$this->assertEquals(4, $pointObj->Point->Y);
	}
}

class GeoPointTest_Obj extends DataObject implements TestOnly {
	static $db = array(
		"Point" => "GeoPoint",
		"Title" => "Varchar",
	);
}

class GeoPointTest_BaseObj2 extends DataObject implements TestOnly {
	static $db = array(
		"Title" => "Varchar",
	);
}

class GeoPointTest_ChildObj2 extends GeoPointTest_BaseObj2 implements TestOnly {
	static $db = array(
		"Point" => "GeoPoint",
	);
}

