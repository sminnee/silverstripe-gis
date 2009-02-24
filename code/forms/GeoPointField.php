<?php
/**
 * Manages a {@link GeoPoint} database field.
 * 
 * @todo Extend CompositeField interface to allow loading/saving of combined values handled by the subclass implementation.
 * @todo Perform subfields to readonly in performReadonlyTransformation()
 *
 * @package gis
 */
class GeoPointField extends FormField {
	public $xField, $yField;
	
	function __construct($name, $title = null, $value = "", $form = null) {
		// naming with underscores to prevent values from actually being saved somewhere
		$this->xField = new NumericField("{$name}[x]", _t('GeoPointField.X', 'Longitude'));
		$this->yField = new NumericField("{$name}[y]", _t('GeoPointField.Y', 'Latitude'));

		parent::__construct($name, $title, $value, $form);
	}
	
	function Field() {
		return "<div class=\"fieldgroup\">" .
			"<div class=\"fieldgroupField\">" . $this->xField->SmallFieldHolder() . "</div>" . 
			"<div class=\"fieldgroupField\">" . $this->yField->SmallFieldHolder() . "</div>" . 
		"</div>";
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
	 * Returns a readonly version of this field.
	 */
	function performReadonlyTransformation() {
		$clone = clone $this;
		$clone->setReadonly(true);
		return $clone;
	}
	
}
?>