<?php
/**
 * Manages a {@link GeoPoint} database field.
 *
 * @package gis
 */
class GeoPointField extends FormField {
	protected $xField, $yField;
	
	function __construct($name, $title = null, $value = "", $form = null) {
		// naming with underscores to prevent values from actually being saved somewhere
		$this->xField = new NumericField("{$name}[x]", _t('GeoPointField.X', 'Longitude'));
		$this->yField = new NumericField("{$name}[y]", _t('GeoPointField.Y', 'Latitude'));

		parent::__construct($name, $title, $value, $form);
	}
	
	function Field() {
		return "<span class=\"fieldgroup\">" .
			$this->xField->Title() . ": " . $this->xField->Field() . " " .
			$this->yField->Title() . ": " . $this->yField->Field() . 
			"</span>";
	}
	
	function setValue($val) {
		$this->value = $val;
		$this->xField->setValue(is_object($val) ? $val->X : $val['x']);
		$this->yField->setValue(is_object($val) ? $val->Y : $val['y']);
	}
	
	function saveInto($dataObject) {
		$fieldName = $this->name;
		$dataObject->$fieldName->X = $this->xField->Value(); 
		$dataObject->$fieldName->Y = $this->yField->Value();
	}

	/**
	 * Returns a readonly version of this field
	 */
	function performReadonlyTransformation() {
		return $this;
	}
	
}
?>