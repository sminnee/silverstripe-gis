<?php
/**
 * Geocode an address-string to a set of coordinates using Google's free
 * geocoding services.
 * 
 * CAUTION: Doesn't store anything on the given fieldname,
 * but relies on {$dataFields} to get a pair of coordinate fields
 * on the saved {@link DataObject} (triggered by {@link Form::saveInto()}.
 * You can't have the {@link $dataFields} as writeable {@link FormField}s in
 * your form, because the form-saving will overwrite the values set here.
 * Either leave them out of the form completely, or make them {@link ReadonlyField}s.
 * 
 * Requirements: allow_url_fopen = on
 *  
 * @author Ingo Schommer, Silverstripe Ltd. (<firstname>@silverstripe.com)
 * @version 0.1
 * @todo Implement CURL with fopen fallback
 * @todo Implement client-side selection when multiple results are found (through validation-errors and javasript)
 * @see http://code.google.com/apis/maps/documentation/services.html#Geocoding_Direct
 * 
 * @package gis
 */
class GeocoderField extends TextField {
	
	public static $geocode_url = "http://maps.google.com/maps/geo?q=%s&output=json&key=%s";
	
	/**
	 * URL for querying the static maps API.
	 * @see http://code.google.com/apis/maps/documentation/staticmaps/
	 *
	 * sprintf-variables:
	 * - lat
	 * - lng
	 * - zoom
	 * - size
	 * - maptype
	 * - apikey
	 * 
	 * @var string
	 */
	public static $googlemaps_static_api_url = "http://maps.google.com/staticmap?center=%s,%s&zoom=%s&size=%s&maptype=%s&key=%s";
	
	/**
	 * Defaults for the static maps image,
	 * e.g. dimensions of 256x256.
	 * 
	 * @see http://code.google.com/apis/maps/documentation/staticmaps/
	 * @var array
	 */
	public $googlemapsStaticApiDefaults = array(
		'lat' => '0',
		'lng' => '0',
		'zoom' => '10',
		'size' => '256x256',
		'maptype' => 'mobile',
	);

	/**
	 * API Key for Google Maps.
	 * (get one at http://code.google.com/apis/maps/signup.html)
	 * 
	 * @var string
	 */
	public static $api_key = '';
	
	/**
	 * Instead of asking for a selection on multiple matches,
	 * default user to first result thats returned.
	 *
	 * @var boolean
	 */
	public $defaultToFirstResult = false;
	
	/**
	 * Show static map image when coordinates are present.
	 * You need to have a DataObject loaded into the form
	 * via Form->loadDataFrom() for this to work
	 * (it needs to get the Lat and Lng attributes from the record).
	 * The CMS-interface automatically takes care of this.
	 *
	 * @var boolean
	 * @see http://code.google.com/apis/maps/documentation/staticmaps/
	 */
	public $showStaticMapImage = false;
	
	/**
	 * Storing the first result from validate()
	 * for later usage in saveInto().
	 *
	 * @var object
	 */
	protected $_cachePlacemark;
	
	/**
	 * Store the coordinates on those pair of
	 * fields on the currently used object
	 * in the form (only works if the field-saving
	 * is triggered by $myForm->saveInto($myObject)).
	 * 
	 * Unset this array to disable auto-saving to these fields.
	 * 
	 * Alternatively, you can use {@link getLat()}
	 * and {@link getLng()}.
	 *
	 * @var array
	 */
	protected $dataFields = array('Lat','Lng');
	
	public function Field() {
		Requirements::css('gis/css/GeocoderField.css');
		
		$html = parent::Field();
		
		$record = $this->form->getRecord();
		
		if(
			$this->showStaticMapImage 
			&& isset($record) 
			&& !empty($record->{$this->dataFields[0]}) 
			&& !empty($record->{$this->dataFields[1]})
		) {
			$spec = array_merge(
				$this->googlemapsStaticApiDefaults,
				array(
					'lat' => $record->{$this->dataFields[0]},
					'lng' => $record->{$this->dataFields[1]},
					'apikey' => self::$api_key,
				)
			);
			
			$imgUrl = sprintf(self::$googlemaps_static_api_url,
				$spec['lng'],
				$spec['lat'],
				$spec['zoom'],
				$spec['size'],
				$spec['maptype'],
				$spec['apikey']
			);

			$html .= <<<HTML
				<div class="googleMapsApiImage">
					<img src="$imgUrl" />
				</div>
HTML;
		}
		
		return $html;
	}
	
	/**
	 * Get geocode from google.
	 *
	 * @see http://code.google.com/apis/maps/documentation/services.html#Geocoding_Direct
	 * @param string $q Place name (e.g. 'Portland' or '30th Avenue, New York")
	 * @return Object Multiple Placemarks and status code
	 */
	public static function get_geocode_obj($q) {
		if(!isset(self::$api_key)) {
			user_error('GeocoderField::get_geocode_obj() needs a valid Google Maps API Key', E_USER_ERROR);
		}

		if(empty($q)) return false;

		$response = file_get_contents(sprintf(self::$geocode_url, urlencode($q), self::$api_key));
		return json_decode($response);
	}
	
	/**
	 * Get first placemark from google, or return false.
	 *
	 * @param string $q
	 * @return Object Single placemark
	 */
	public static function get_placemark($q) {
		$responseObj = self::get_geocode_obj($q);
		
		if(!$responseObj || $responseObj->Status->code != '200') {
			return false;
		} else {
			return $responseObj->Placemark[0];
		}
	}
	
	public function validate() {
		if(empty($this->value)) return false;
		
		// cache
		if($this->_cachePlacemark) return $this->_cachePlacemark;
		
		// get geocode from google
		$responseObj = self::get_geocode_obj($this->Value());
		
		$validator = $this->form->getValidator();
		
		// TODO Better evaluation of status codes
		if(!$responseObj || $responseObj->Status->code != '200') {
			$validator->validationError(
				$this->Name(), 
				_t('GeocoderField.LOCATIONNOTFOUND',"Location can't be found"), 
				"validation", 
				false
			);
			return false;
		}
		
		$isUnique = (count($responseObj->Placemark) == 1);
		if(!$isUnique && !$this->defaultToFirstResult) {
			$validator->validationError(
				$this->Name(), 
				_t('GeocoderField.LOCATIONNOTUNIQUE',"Location is not unique, please be more specific"), 
				"validation", 
				false
			);
			return false;
		}
		
		$placemark =  $responseObj->Placemark[0];

		return ($placemark);
	}

	/**
	 * Sets query-string as normal value,
	 * but also queries the google geocoder
	 * to get the first placemark and caches it.
	 *
	 * @param unknown_type $value
	 */
	public function setValue($value) {
		$this->value = $value;
		if($this->value) {
			$placemark = self::get_placemark($this->value);
			if($placemark) {
				$this->_cachePlacemark = $placemark;
			}			
		}
		
	}
	
	public function saveInto($record) {
		if(!$this->_cachePlacemark) return false;
		
		if(isset($this->dataFields[0]) && !$record->hasField($this->dataFields[0])) {
			user_error('GeocoderField::saveInto Please define a database-field "' . $this->dataFields[0] . '" on the saved DataObject', E_USER_NOTICE);
		}
		if(isset($this->dataFields[1]) && !$record->hasField($this->dataFields[1])) {
			user_error('GeocoderField::saveInto Please define a database-field "' . $this->dataFields[1] . '" on the saved DataObject', E_USER_NOTICE);
		}

		// save to object fields (defaults to "Lat"/"Lng"), if array is not unset by user
		if(isset($this->dataFields)) {
			$record->setCastedField($this->dataFields[0], $this->getLat());
			$record->setCastedField($this->dataFields[1], $this->getLng());
		}
	}
	
	/**
	 * Get address of first result.
	 *
	 * @return string
	 */
	public function getAddress() {
		if(!isset($this->_cachePlacemark)) $this->_cachePlacemark = self::get_placemark($this->Value());
		
		return $this->_cachePlacemark->address;
	}
	
	/**
	 * Get latitude of first result.
	 *
	 * @return float
	 */
	public function getLat() {
		if(!isset($this->_cachePlacemark)) $this->_cachePlacemark = self::get_placemark($this->Value());
		
		return (float)$this->_cachePlacemark->Point->coordinates[0];
	}

	/**
	 * Get longitude of first result.
	 *
	 * @return float
	 */
	public function getLng() {
		if(!isset($this->_cachePlacemark)) $this->_cachePlacemark = self::get_placemark($this->Value());
		
		return (float)$this->_cachePlacemark->Point->coordinates[1];
	}
	
	/**
	 * Set coordinate storage fields.
	 *
	 * @param array $arr
	 */
	public function setDataFields($arr) {
		$this->dataFields = $arr;
	}
	
	/**
	 * Get coordinate storage fields.
	 *
	 * @return array
	 */
	public function getDataFields() {
		return $this->dataFields;
	}
	
	/**
	 * Clear the Placemark (first result)
	 * that is cached when {@link validate()} or
	 * {@link setValue()} are called.
	 */
	public function clearCache() {
		unset($this->_cachePlacemark);
	}
}
?>