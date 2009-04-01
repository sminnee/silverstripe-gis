<?php

class CSVDataFormatter extends DataFormatter {
	/**
	 * @todo pass this from the API to the data formatter somehow
	 */
	static $api_base = "api/v1/";
	
	protected $outputContentType = 'text/csv';
	
	public function supportedExtensions() {
		return array(
			'csv'
		);
	}
	
	public function supportedMimeTypes() {
		return array(
			'text/csv',
			'application/csv',
			'text/comma-separated-value'
		);
	}
	
	public function convertDataObject(DataObjectInterface $obj, $fields = null) {
		parent::convertDataObject($obj, $fields);
	}
		
	public function convertDataObjectWithoutHeader(DataObject $obj, $fields = null, $relations = null) {
		
		$valuesArray = array();
		foreach ($this->getFieldsForObj($obj) as $fieldName => $fieldType) {
			
			$fieldValue = $obj->$fieldName;
			
			if(is_object($fieldValue) && is_subclass_of($fieldValue, 'Object') && $fieldValue->hasMethod('toCSV')) {
				$valuesArray[] = '"' . $fieldValue->toCSV() . '"';
			}
			else {
				$valuesArray[] = '"' . $fieldValue . '"';
			}
		}
		$csv = implode(',', $valuesArray) . "\r\n";
		
		return $csv;
	}

	/**
	 * Generate an XML representation of the given {@link DataObjectSet}.
	 * 
	 * @param DataObjectSet $set
	 * @return String XML
	 */
	public function convertDataObjectSet(DataObjectSet $set, $fields = null) {
		Controller::curr()->getResponse()->addHeader("Content-Type", "text/csv");
		
		// csv header
		$headerArray = array();
		
		foreach($this->getFieldsForObj($set->First()) as $fieldName => $fieldType) {
			$headerArray[] = $fieldName;
			$fieldValue = '"' . $set->First()->$fieldName . '"';
		}
		$csv = implode(',', $headerArray) . "\r\n";
		
		foreach($set as $item) {
			if($item->canView()) $csv .= $this->convertDataObjectWithoutHeader($item, $fields);
		}
		
		return $csv;
	}
	

}