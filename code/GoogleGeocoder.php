<?php

define("GOOGLEMAPS_HOST", "maps.google.com");

/**
 * Server-side connector to the Google Geocoding API.
 * Uses GOOGLEMAPS_API_KEY for the API key
 * 
 * Usage:
 * <pre>
 * $g = new GoogleGeocoder();
 * $geoPoint = $g->addressToPoint("123 Victoria St, Te Aro, Wellington, NZ");
 * $otherGeoPoint = $g->addressPartsToPoint("123", "Adelaide Rd", "Newtown", "Wellington", "NZ");
 * </pre>
 */
class GoogleGeocoder {
	

	/**
	 * Returns a Google Geopoint matching the given address parts
	 */
	function addressPartsToPoint($streetNumber, $street, $suburb, $city, $country) {
		return $this->addressToPoint("$streetNumber, $street, $suburb, $city, $country");
	}

	/**
	 * Returns a Google Geopoint matching the given address.
	 */
	function addressToPoint($address) {
		$baseURL = "http://" . GOOGLEMAPS_HOST . "/maps/geo?output=xml" . "&key=" . GOOGLEMAPS_API_KEY;
		$requestURL = $baseURL . "&q=" . urlencode($address);
		
		$delay = 0;
		
		$status = "620";
		while(strcmp($status, "620") == 0) {		
	    	$xml = simplexml_load_file($requestURL) or user_error("Can't access $url", E_USER_WARNING);
			if(!$xml) return;
			
		    $status = $xml->Response->Status->code;
		    if (strcmp($status, "200") == 0) {
				// Successful geocode
				$coordinates = $xml->Response->Placemark->Point->coordinates;
				$coordinatesSplit = split(",", $coordinates);
				// Format: Longitude, Latitude, Altitude
				$lat = $coordinatesSplit[1];
				$lng = $coordinatesSplit[0];
				return GeoPoint::from_x_y($lat, $lng);

		    } else if (strcmp($status, "620") == 0) {
				// sent geocodes too fast
				$delay += 100000;
			}
		}
	}
}

?>