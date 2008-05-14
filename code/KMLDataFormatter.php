<?php

/**
 * DataFormatter that presents geographic data as a KML file.
 * @todo There needs to be a better way of the marketdemandmap demand data to talk to this interface.
 * 
 * @package gis
 */
class KMLDataFormatter extends DataFormatter {
	/**
	 * @todo pass this from the API to the data formatter somehow
	 */
	static $api_base = "api/v1/";
	
	public function supportedExtensions() {
		return array('kml');
	}
	
	public function convertDataObject(DataObjectInterface $obj) {
		Controller::curr()->getResponse()->addHeader("Content-type", "application/vnd.google-earth.kml+xml");
		return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<kml xmlns=\"http://earth.google.com/kml/2.2\">" 
			. $this->convertDataObjectWithoutHeader($obj);
	}
		
		
	public function convertDataObjectWithoutHeader(DataObject $obj) {
		$className = $obj->class;
		$id = $obj->ID;
		$objHref = Director::absoluteURL(self::$api_base . "$obj->class/$obj->ID");
		
		$Title = $obj->obj('OrganisationTitle')->XML();
		$Content = "";
		$PointX = $obj->Point->X;
		$PointY = $obj->Point->Y;
	
		$content = <<<KML
			<Placemark>
				<name>Point: $Title</name>
				<Point>
					<coordinates>$PointX,$PointY,0</coordinates>
				</Point>
			</Placemark>
KML;
	
		return $content;
	
		/*
		foreach($obj->has_one() as $relName => $relClass) {
			$fieldName = $relName . 'ID';
			if($obj->$fieldName) {
				$href = Director::absoluteURL(self::$api_base . "$relClass/" . $obj->$fieldName);
			} else {
				$href = Director::absoluteURL(self::$api_base . "$className/$id/$relName");
			}
			$json .= "<$relName linktype=\"has_one\" href=\"$href.xml\" id=\"{$obj->$fieldName}\" />\n";
		}

		foreach($obj->has_many() as $relName => $relClass) {
			$json .= "<$relName linktype=\"has_many\" href=\"$objHref/$relName.xml\">\n";
			$items = $obj->$relName();
			foreach($items as $item) {
				//$href = Director::absoluteURL(self::$api_base . "$className/$id/$relName/$item->ID");
				$href = Director::absoluteURL(self::$api_base . "$relClass/$item->ID");
				$json .= "<$relClass href=\"$href.xml\" id=\"{$item->ID}\" />\n";
			}
			$json .= "</$relName>\n";
		}

		foreach($obj->many_many() as $relName => $relClass) {
			$json .= "<$relName linktype=\"many_many\" href=\"$objHref/$relName.xml\">\n";
			$items = $obj->$relName();
			foreach($items as $item) {
				$href = Director::absoluteURL(self::$api_base . "$relClass/$item->ID");
				$json .= "<$relClass href=\"$href.xml\" id=\"{$item->ID}\" />\n";
			}
			$json .= "</$relName>\n";
		}
		*/
	}

	/**
	 * Generate an XML representation of the given {@link DataObjectSet}.
	 * 
	 * @param DataObjectSet $set
	 * @return String XML
	 */
	public function convertDataObjectSet(DataObjectSet $set) {
		//Controller::curr()->getResponse()->addHeader("Content-type", "application/vnd.google-earth.kml+xml");
		$className = $set->class;
	
		$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<kml xmlns=\"http://earth.google.com/kml/2.2\">\n";
		$xml .= "<Folder>\n<name>Folder</name>\n";
		foreach($set as $item) {
			if($item->canView()) $xml .= $this->convertDataObjectWithoutHeader($item) . "\n";
		}
		$xml .= "</Folder>";
		$xml .= "</kml>";
		
		$fh = fopen("../assets/test.kml", "w");
		fwrite($fh, $xml);
		fclose($fh);
	
		return $xml;
	}
}