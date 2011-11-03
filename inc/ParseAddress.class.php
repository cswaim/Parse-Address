<?php
/**
 * @author Jonathon Byrd
 * @desc This class has the responsibility of parsing an address to ensure
 * that all address are of a valid format.
 * 
 * One unique thing to keep in mind is that this class has been designed
 * to not be dependent on anything, such as commas within the address.
 * The ParseStatePostalCodeCountry method actually removes the commas
 * from the address
 * 
 * WARNING : if the address is every completely torn apart, such as
 * incorrectly parsed, and the culprit has to deal with the street address
 * and the city, have a look at the _parseCity function. I'm not conformable
 * with how the explode is locating the split between the street and the
 * city
 * 
 * Right now the function is dependent on a list of cities to validate that 
 * we are parsing properly.
 *
 *@author Criss Swaim, The Pineridge Group, LLC
 *@link http://www.tpginc.net
 *@since 11/02/2011
 *
 *@desc  Updated this fork of the class to parse multi-word cities and 
 * corrected some lookups routines. The useage of the class has changed as indicated 
 * below
 *
 * Usage:
 * 		$pa = new ParseAddress;
 *      // because of conflict between state codes and country codes, set the country if zip codes are missing
 *		$pa->set("country","US");
 *		$pa->set("default_state","CA",true);						//optional
 *		$pa->set("default_country","US",true);                		//optional
 *
 *		$rtn_addr = $pa->parseAddress($addr);
 *
 *		if ($rtn_addr['errors']) { handle errors}	
 *		if ($rtn_addr['warnings']) { handle warnings}	
 *		if ($rtn_addr['info']) { handle conditional}		
 * 
 */
class ParseAddress extends ObjectBase
{
	/**
	 * default country if one is not provided 
	 * 
	 * @var string
	 */
	public $default_country = 'USA';
	/**
	 * default state if one is not provided 
	 * 
	 * @var string
	 */
	public $default_state = null;
	
	/**
	 * postal code 
	 * 
	 * @var string
	 */
	public $postal_code = null;
	
	/**
	 * country code
	 * 
	 * @var string
	 */
	public $country = null;
	
	/**
	 * state code
	 * 
	 * @var string
	 */
	public $state = null;
	
	/**
	 *  city value
	 * 
	 * @var string
	 */
	public $city = null;
	
	/**
	 *  array used to pase city - used with multi word city names
	 * 
	 * @var string
	 */
	private $city_arr = array();
	
	/**
	 * full street address
	 * 
	 * @var string
	 */
	public $street_addr = null;
	
	/**
	 * 
	 * 
	 * @var string
	 */
	public $subpremise = null;
	
	/**
	 * 
	 * 
	 * @var string
	 */
	public $street_type = null;
	
	/**
	 * 
	 * 
	 * @var string
	 */
	public $direction = null;
	
	/**
	 * 
	 * 
	 * @var string
	 */
	public $street_number = null;
	
	/**
	 * 
	 * 
	 * @var string
	 */
	public $street_name = null;
	
	/**
	 * Property contains the full raw address
	 * 
	 * @var string
	 */
	public $_original_address = null;
	
	/**
	 * Property contains the clean address
	 * 
	 * @var string
	 */
	public $_clean_address = null;
	
	/**
	 * Property contains the method
	 * 
	 * @var string
	 */
	private $_method = null;
	
	/**
	 * 
	 * 
	 * @var object
	 */
	private $_arrays = null;
	/**
	 * country table for lookup by code
	 * 
	 * @var object
	 */
	private $_countries = null;
	/**
	 * country table for lookup by name
	 * 
	 * @var object
	 */
	private $_countries_reversed = null;
	/**
	 * state table for lookup by code
	 * 
	 * @var object
	 */
	private $_states = null;
	/**
	 * state table for looup by name
	 * 
	 * @var object
	 */
	private $_states_reversed = null;
	/**
	 * city/state lookup
	 * 
	 * @var object
	 */
	private $_cities = null;
	/**
	 * street table for lookup by code
	 * 
	 * @var object
	 */
	private $_streets = null;
	/**
	 * street table for looup by name
	 * 
	 * @var object
	 */
	private $_streets_reversed = null;
	/**
	 * directions table for lookup by code
	 * 
	 * @var object
	 */
	private $_directions = null;
	/**
	 * directions table for looup by name
	 * 
	 * @var object
	 */
	private $_directions_reversed = null;
	 
	 /**
	 * if true, then google is used to parse address if other routines fail.
	 * 
	 * @var bool 
	 */
	public $parse_google = false;
	
	/**
	 * if true, print messages through processing.
	 * 
	 * @var bool 
	 */
	private $_debug = false;
	
	/**
	 * Constructor.
	 * 
	 * Method is responsible for constructing the object and starting the process
	 * 
	 * @param bool   debug
	 * @return void
	 */
	public function __construct( $debug=false )
	{
		//initializing object properties
		$this->_debug = $debug;
		
		//load edit tables
		$this->_countries = AddressHelper::getArray('_countries');
		$this->_countries_reversed = AddressHelper::getReverseArray('_countries');
		$this->_states = AddressHelper::getArray('_states');
		$this->_states_reversed = AddressHelper::getReverseArray( '_states' );
		$this->_cities = AddressHelper::getArray('_cities');
		
		$this->_streets = AddressHelper::getArray('_streets');
		$this->_streets_reversed = AddressHelper::getReverseArray( '_streets' );
		$this->_directions = AddressHelper::getArray('_directions');
		$this->_directions_reversed = AddressHelper::getReverseArray( '_directions' );
		
//		$this->_original_address = $address;
//		$this->_clean_address = $this->clean( $address );
//		
//		if ($this->_debug) {
//			echo "<br><br>--- entering ParseAddress--- <br>";
//			echo "<br />  orig addr: ".$address;
//			echo "<br /> clean addr: ".$this->_clean_address;
//		}
//		
//		
//		$this->controller();
	}
	/**
	 * Parse the address 
	 * 
	 * Method is here to determine if the object properties have values
	 * if they don't then that's a possible invalid address
	 * 
	 * @param string|array $address
	 * @return array
	 */
	public function parseAddress( $address = null )
	{
		$this->_original_address = $address;
		$this->_clean_address = $this->clean( $address );
		
		if ($this->_debug) {
			echo "<br><br>--- entering ParseAddress--- <br>";
			echo "<br />  orig addr: ".$address;
			echo "<br /> clean addr: ".$this->_clean_address;
		}
		
		$this->initialize();
		$this->controller();
		$addr_arr = $this->toArray();
		//set error messages
		if ($this->errorMsgs) {
			foreach ($this->errorMsgs as $key => $value) {
				if ($value[0] == "w") {
					$_warnings[] = $value[1];
				} elseif ($value[0] == "e") {
					$_errors[] = $value[1];
				} else {
					$_info[] = $value[1];
				}
			}
		}
		if (!is_null($_errors)) {
			$addr_arr["errors"] = $_errors;
		}
		if (!is_null($_warnings)) {
			$addr_arr["warnings"] = $_warnings;
		}
		if (!is_null($_info)) {
			$addr_arr["info"] = $_info;
		}
		return $addr_arr;
	}
	
	/**
	 * Check that the property has a value
	 * 
	 * Method is here to determine if the object properties have values
	 * if they don't then that's a possible invalid address
	 * 
	 * @param string $property
	 * @return boolean
	 */
	public function check( $property = null )
	{
		//the full property is the global check, that something exists
		if (is_null($property))
		{
			$property = 'full';
		}
		
		//checking the property values
		if (!isset($this->$property)) return false;
		if (is_null($this->$property)) return false;
		if (strlen(trim($this->$property)) < 1) return false;
		
		return true;
	}
	
	/**
	 * City
	 * 
	 * @return string
	 */
	public function city()
	{
		return ucwords(strtolower($this->city));
	}
	
	/**
	 * Magic Call Method
	 * 
	 * Method allows us to target all object properties through individual methods
	 * allowing us to easily override the default method properties throughout the system
	 * by easily adding the override and without changing every method caller
	 * 
	 * @param $method
	 * @param $args
	 */
	public function __call($method, $args)
	{
		//initializing variables
		$switch = substr($method,0,3);
		
		//allows use to determine what to do using the first three characters of the method call
		switch($switch)
		{
			case 'get': 
				//formatting for a get variable
				$method = preg_replace('/(?<=[a-z])(?=[A-Z])/','_',$method);
				$property = strtolower(str_replace('get_','',$method));
				
				return $this->get( $property, $args ); 
				break;
				
			case 'set': 
				//formatting for a set variable
				$property = strtolower(str_replace('set','',$method));
				
				return $this->set( $property, @$args[0], @$args[1] ); 
				break;
				
			default: 
				//initiailzing variables
				$property = $method;
				
				if (isset($this->$property)) return $this->$property; break;
		}
		return false;
	}
	
	/**
	 * Initialize.
	 * 
	 * Method is responsible for preparing the work fields
	 * 
	 * @return void
	 */
	protected function initialize()
	{
		$this->errorMsgs = null;
		$this->city_array = null;
	}
	
	/**
	 * Controller.
	 * 
	 * Method is responsible for starting the process of parsing the given
	 * address, doing whatever is possible
	 * 
	 * @return void
	 */
	protected function controller()
	{
		//Array's are easy, just need validating
		if ($this->parseArray())
			return true;
		
		//attempting to parse this address ourselves
		if ($this->parseString()) 
			return true;

		//letting google have the last shot at this
		//Google has a 24 hour quota of around a few hundred requests
		//this is why we only ask them if we have to
		if ($parse_google) {
			if ($this->parseGoogle()) 
				return true;
		}
		
		return false;
	}
	
	/**
	 * Clean this address part
	 * 
	 * Method will perform simple string cleaning exercises on this part in order to
	 * return a clean value
	 * 
	 * @param $part
	 * @return string
	 */
	public function clean( $string = null )
	{
		//reasons to return empty
		if (is_array($string)) return $string;
		if (is_null($string)) return '';
		if (strlen(trim($string)) < 1) return '';
		
		//initializing variables
		$string = strtoupper(trim($string));
		$string = str_replace(array(",","  "), " ", $string);
		
		return $string;
	}
	
	/**
	 * Get Whats Left of the Original address
	 * 
	 * Method is responsible for returning whats left of the original
	 * address that hasn't been mapped to the object yet.
	 * 
	 * @return boolean
	 */
	public function getWhatsLeft()
	{
		//initializing variables
		$addr = strtoupper(trim($this->_clean_address));
		$properties = $this->getProperties();
		
		//reasons to return
		if ( strlen(trim($addr)) <1 ) return false;
		if ( empty($properties) ) return false;
		
		//the real work
		foreach ($properties as $property => $value)
		{
			//initializing variables
			$displace = strrpos($addr, $value);
			$length = strlen($value);
			
			$addr = substr_replace($addr, "", $displace, $length);
		}
		
		//reasons to return
		if ( strlen(trim($addr)) <1 ) return false;
		
		return $addr;
	}
	
		
	/**
	 * Is this part a city
	 * 
	 * Method will return the postal code if this is a legitamite state
	 * Otherwise the method will return false
	 * 
	 * @param string $part
	 */
	public function isCity( $string = null, &$parts=null)
	{
		//reasons to fail
		if (is_null($string)) return false;
		if (is_null($this->state)) return false;
		if (strlen(trim($string)) < 2) return false;
		
		//initializing variables
		$string = strtoupper(trim($string));
		$temp = $this->clean($string);
		
		if ( in_array($temp, $this->_cities[$this->state])) {
			$key = array_search($temp, $this->_cities[$this->state]);
			unset($parts[$key]);
			return $string;
		} else {
			// use parts array to check for multi-word city name
			if ($this->_debug) {echo "<br>isCity chk for multi-word city ";}
			if (!empty($parts)) {			
				$key = array_search($temp, $parts);
				if ($this->_debug) {echo "city loop: ".$key.'-'.$temp.'-';}
				// loop through city arr, build city name and check it
				for ($i=1;$i<$key;$i++) {
					if ($this->_debug) {echo "<br>isCity chk for multi-word city ";}
					$city_part = strtoupper(trim($parts[$key - $i]));
					$temp = $city_part." ".$temp;
					//unset used parts of city
					if ( in_array($temp, $this->_cities[$this->state])) {
						$x=$key-$i;
						for ($k=$x;$k<=$key;$k++) {
							unset($parts[$k]);
						}
						$this->parseSet("city", $temp);
						return $temp;
					}
				}
			}
		}

		return false;
	}
	
	/**
	 * Is this a Country?
	 * 
	 * Method is responsible for checking to see if the given string is
	 * a valid country andwill return the full country name if this is a legitamite country
	 * Otherwise it returns false
	 * 
	 * @param string $string
	 * @return string|false
	 */
	public function isCountry( $string = null )
	{
		if ($this->_debug) {echo "<br>isCountry test - ".$string;}
		//reasons to fail
		if (is_null($string)) return false;
		if (strlen(trim($string)) < 1) return false;
		
		//initializing variables
		$string = strtoupper(trim($string));
		$temp = $this->clean($string);

		//reasons to believe that this is a country
		if (isset($this->_countries[$temp])) return $string;
		if (isset($this->_countries_reversed[$temp]))return $string;
		
		//set default country
		if (is_null($this->country)) {
			if (is_null($this->default_country)) { 
				$this->errorMsgs[] = array("e","county not found - default country not set ");
			} else {
				$this->country = $this->default_country;
				$this->errorMsgs[] = array("i","country not found - using default ".$this->default_country);
			}
		}
		
		return false;
	}
	
	/**
	 * Is this part a direction?
	 * 
	 * Method will determine if the part is a direction
	 * Otherwise it returns false
	 * 
	 * @param string $part
	 * @return string|false
	 */
	public function isDirection( $string = null )
	{
		//reasons to fail
		if (is_null($string)) return false;
		if (strlen(trim($string)) < 1) return false;
		
		//initializing variables
		$string = strtoupper(trim($string));
		$temp = str_replace('.', '', $this->clean($string));
		
		//reasons to believe that this is a direction
		if (isset($this->_directions[$temp])) return $string;
		if (isset($this->_directions_reversed[$temp]))return $string;
		
		return false;
	}
	
	/**
	 * Is this part a postal code
	 * 
	 * Method will return the postal code if this is a legitamite postal code
	 * Otherwise the method will return false
	 * 
	 * @param string $part
	 * @return string|false
	 */
	public function isPostalCode( $string = null )
	{
		//reasons to fail
		if (is_null($string)) return false;
		if (strlen(trim($string)) < 4) return false;
		
		//initializing variables
		$string = strtoupper(trim($string));
		$pattern_usa = '/[0-9]{5}(?:-[0-9]{4})?/';
		$pattern_canada = '/([a-ceghj-npr-tv-z]){1}[0-9]{1}[a-ceghj-npr-tv-z]{1}[0-9]{1}[a-ceghj-npr-tv-z]{1}[0-9]{1}/i';
		
		//Checking to see if this is a USA valid match
		preg_match($pattern_usa, $string, $matches);
		if (isset($matches[0]))
		{
			return $string;
		}
		
		//Checking to see if this is a USA valid match
		preg_match($pattern_canada, $string, $matches);
		if (!isset($matches[0])) return false;
		
		return $string;
	}
	
	/**
	 * Is this part a state
	 * 
	 * Method will return the state if this is a legitamite state
	 * Otherwise the method will return false
	 * 
	 * @param string $part
	 * @return string|false
	 */
	public function isState( $string = null )
	{
		//reasons to fail
		if (is_null($string)) return false;
		if (strlen(trim($string)) < 2) return false;
		
		//initializing variables
		$string = strtoupper(trim($string));
		$string = $this->clean($string);
		
		$temp = $string;
		
		
		
		//reasons to believe that this is a state
		if (isset($this->_states[$temp])) return $string;
		if (isset($this->_states_reversed[$temp]))return $string;
		
		//set to default state
		if (is_null($this->state)) {
			if (is_null($this->default_state)) { 
				$this->errorMsgs[] = array("e","state not found - default state not set ");
			} else {
				$this->state = $this->default_state;
				$this->errorMsgs[] = array("i","state not found - using default ".$this->default_state);
			}
		}
		
		return false;
	}
	
	/**
	 * Is this a Street Name
	 * 
	 * Method will determine if this could possible be a street name
	 * Otherwise it returns false
	 * 
	 * @param string $part
	 * @return string|false
	 */
	public function isStreetName( $string = null )
	{
		//reasons to fail
		if (is_null($string)) return false;
		if (strlen(trim($string)) < 1) return false;
		
		//initializing variables
		$string = strtoupper($this->clean($string));
		$string = $this->NumbersToWords($string);
		
		return $string;
	}
	
	/**
	 * Is this a Street Address Number
	 * 
	 * Method will determine if this could possible be a street number
	 * Otherwise it returns false
	 * 
	 * @param string $part
	 */
	public function isStreetNumber( $string = null )
	{
		//reasons to fail
		if (is_null($string)) return false;
		if (strlen(trim($string)) < 1) return false;
		
		//initializing variables
		$patterns = array("/[^0-9]/");
		$string = trim($string);
		
		//cleaning the number
		$number = preg_replace($patterns, "", $string);
		if ($number == $string) return $string;
		
		return false;
	}
	
	/**
	 * Is this Part street type
	 * 
	 * Method will determine if this part is a street type of some kind
	 * Otherwise returning false
	 * 
	 * @param string $part
	 */
	public function isStreetType( $string = null )
	{
		//reasons to fail
		if (is_null($string)) return false;
		if (strlen(trim($string)) < 1) return false;
		
		//initializing variables
		$string = strtoupper(trim($string));
		$temp = $this->clean($string);
		
		//reasons to believe that this is a country
		if (isset($this->_streets[$temp])) return $string;
		if (isset($this->_streets_reversed[$temp]))return $string;
		
		return false;
	}
	
	/**
	 * Is this a sub premise?
	 * 
	 * Method will determine if the string has a digit, as we know that cities
	 * do not contain digits
	 * 
	 * @param string $part
	 * @return string|false
	 */
	public function isSubPremise( $string = null )
	{
		//reasons to fail
		if (is_null($string)) return false;
		if (strlen(trim($string)) < 1) return false;
		
		//initializing variables
		$pattern = "/([\d]+)$/";
		$string = trim($string);
		
		//cleaning the number
		preg_match($pattern, $string, $matches);
		
		//if there is no sub premise, then fail
		if (!isset($matches[0])) return false;
		
		return $string;
	}
	
	/**
	 * Is the Address Valid?
	 * 
	 * Method is designed to check specificaly whether or not the complete
	 * address is valid
	 * 
	 */
	public function isValid()
	{
		//initiailizing variables
		$valid = true;
		$properties = array("country","state","postal_code","city","street");
		
		foreach($properties as $property)
		{
			if ($valid && ($valid = $this->check( $property )))
			{
				$valid = true;
			}
		}
		
		return $valid;
	}
	
	/**
	 * Convert numbers to words
	 * 
	 * Method is responsible for converting numbers to words
	 * 
	 * @param string $string
	 * @return boolean
	 */
	public function NumbersToWords( $string = null )
	{
		//reasons to return
		if ( is_null($string) ) return false;
		
		//initializing variables
		$_ones = AddressHelper::getReverseArray( '_ones' );
		$_tens = AddressHelper::getReverseArray( '_tens' );
		
		//start replacing words with digits
		foreach($_ones as $key => $one)
		{
			$string = str_replace(strtoupper($key), strtoupper($one), $string);
		}
		foreach($_tens as $key => $one)
		{
			$string = str_replace(strtoupper($key), strtoupper($one), $string);
		}
		
		return $string;
	}
	
	/**
	 * Parse the Array
	 * 
	 * Method is responsible for mapping this array to the object properties
	 * 
	 * @return boolean
	 */
	protected function parseArray()
	{
		//reasons to return
		if ( !is_array($this->_clean_address) ) return false;
		
		//initializing variables
		$count = count($this->_clean_address);
		$i = 1;
		
		foreach ($this->_clean_address as $key => $part)
		{
			$i++;
			//collecting the national parts
			if ($this->parseSet("postal_code", $part)) continue;
			if ($this->parseSet("country", $part)) continue;
			if ($this->parseSet("state", $part)) continue;
			if ($this->parseSet("city", $part)) continue;
			
			//parse in a new manor if 
			if ($temp = $this->parseStreet($part)) continue;
			
		}
		
		return true;
	}
	
	/**
	 * Let Google Parse it for us
	 * 
	 * Method will preset the google variables and the send the address to
	 * google for proper parsing
	 * 
	 * Method sets the class properties directly, that's why its private
	 * 
	 * @access protected
	 * @return boolean
	 */
	protected function parseGoogle()
	{
		//initializing variables
		$google = 'http://maps.google.com/maps/api/geocode/json?sensor=true&address=';
		
		//set method
		$this->_method = 'google';
		//Just have google decode it for us
		if (is_array($this->_clean_address))
		{
			$this->_clean_address = implode(' ',$this->_clean_address);
		}
		
		$json = file_get_contents($google.urlencode($this->_clean_address));
		$address = json_decode($json);
		
		//fail if google was 100%
		if (!isset($address->status) || $address->status != 'OK') return false;
		
		//google returns a perfect csv format
		$this->_clean_address = (string)$address->results[0]->formatted_address;
		$this->_clean_address = $this->clean( $this->_clean_address );
		
		//attempting to parse this address ourselves
		if ($this->parseString()) 
			return true;
		
		return false;
	}
	
	/**
	 * Parse the address string
	 * 
	 * Method is responsible for parsing the string that is an address
	 * 
	 * @return boolean
	 */
	protected function parseString()
	{
		if ($this->_debug) { echo "<br> entering parseString";}
		//initializing variables
		$string = $this->clean( trim($this->_clean_address) );
		$parts = explode(" ", $string);
		$leftovers = "";
		$max_size = sizeof($parts)-1;
		
		//reasons to return
		if (!isset($parts[1])) return false;
		
		//we're reversing the array and disecting it backwords
		foreach (array_reverse($parts, true) as   $key => $part)
		{
						
			if ($this->_debug) {echo "<br>parsing: ".$key.' - '.$part.'  ('.$max_size.')';}
			
			//COUNTRY
			// eliminate if not last item
			if (is_null($this->country)
				&& $this->isCountry( $part) 
				&& ($key == $max_size)
				&& $this->parseSet("country", $part))
			{
				if ($this->_debug) {echo "<br>setting country - ".$part;}
				unset($parts[$key]);
				continue;
			}
			
			//POSTAL CODE
			if (is_null($this->postal_code)
				&& $this->isPostalCode( $part) 
				&& $this->parseSet("postal_code", $part))
			{
				if ($this->_debug) {echo "<br>setting postal code - ".$part;}
				unset($parts[$key]);
				continue;
			}
			
			//STATE
			if (is_null($this->state)
				&& ($key >= $max_size-3)
				&& $this->isState( $part) 
				&& $this->parseSet("state", $part))
			{
				if ($this->_debug) {echo "<br>setting state - ".$part;}
				unset($parts[$key]);
				continue;
			}
			
			//CITY
			//load parts to city array for multi-word city names
			//isCity will unset the part.  If a multi-word city is found, multiple parts are unset
			if (is_null($this->city)
				&& $this->isCity($part, $parts)) {
				continue;
			}
		}
		
		reset($parts);
		$leftovers = implode(" ",$parts);
		
		if ($this->_debug) { 
			echo "<br> leftovers: ".$leftovers;
			#echo "<br>whats left: ".$this->getWhatsLeft();
			echo "<br /> parts array <pre>";
			print_r( $parts);
			echo "</pre>";
		}
		
		// set street address with 
		$this->street_addr = ucwords(strtolower($leftovers));
		return true;
		//STREET PARSING
		//if ($this->parseStreet( $this->getWhatsLeft() ))
		//	return true;
		return false;
	}
	
	/**
	 * Parse the street
	 * 
	 * Method is responsible for parsing the street address out of the given
	 * string
	 * 
	 * @param string $string
	 * @return string|boolean
	 */
	public function parseStreet( $string = null )
	{
		//initializing variables
		if ($this->_debug) { echo "<br><br>Entering parseStreet with: ".$string;}
		$leftovers = "";
		$string = $this->clean( $string );
		
		if ( strlen($this->_clean_address) <1 )
		{
			$this->_clean_address = $string;
		}
		
		$parts = explode(" ", $string);
		
		//reasons to return
		if (!isset($parts[1])) return false;
		
		//parse the street addr
		foreach ($parts as $key => $part)
		{
			//STREET NUMBER
			if ($this->isStreetNumber( $part) 
				&& $this->parseSet("street_number", $part))
			{
				if ($this->_debug) { echo "<br>unset strNu: ".$key.' - '.$part; }
				unset($parts[$key]);
			}
						
			//STREET TYPE
			elseif ($this->isStreetType($part)
			 && ($this->parseSet("street_type", $part))
			 && ($key > 1))    // type must be after st name 
			{
				if ($this->_debug) { echo "<br>unset strType: ".$key.' - '.$part; }
				unset($parts[$key]);
				//continue;
			}
			
			//DIRECTION :: next is to locate a direction
			elseif ($this->isDirection($part ) 
			 && $this->parseSet("direction", $part))
			{
				unset($parts[$key]);
				//continue;
			}
			
			//SUBPREMISE :: first thing is to remove any subpremise
			elseif ($this->isSubPremise( $part )
			 && $this->parseSet("subpremise", $part))
			{
				unset($parts[$key]);
			} else {
				$leftovers .= " ".$part;
			}
			
		}
		
		if ($this->_debug) {
			echo "<br> leftovers: ".$leftovers;
			echo '<br>part array: <pre>';
			print_r($parts);
			echo '</pre>';
		}
		
		//anything left is the street name
		//STREET NAME
		if ($this->isStreetName( $leftovers )) {
			$this->parseSet("street_name", $leftovers);
		}


		//build street addr
		$this->street_addr = $this->street_number.' '.$this->street_name.' '.$this->street_type;
		if ($this->direction != ""){
			$this->street_addr .= ' '.$this->direction;
		}
		if ($this->subpremise != ""){
			$this->street_addr .= ' '.$this->subpremise;
		}
		
		if ($this->_debug) {
			echo "<br> street addr: ".$this->street_addr;
			echo "<br>stringPars return: ".$this->getWhatsLeft();
		}
		
		return $this->getWhatsLeft();
	}
	
	/**
	 * Set during parse
	 * 
	 * Method is responsible for setting the value if it has not already 
	 * been set, this method is reserved for when parsing a string or
	 * an array
	 * 
	 * Return true if we set it
	 * 
	 * @param string $property
	 * @param string $value
	 * @return boolean
	 */
	protected function parseSet( $property = null, $value = null )
	{
		//reasons to return
		if ( is_null($property) ) return false;
		if ( is_null($value) ) return false;
		
		//initializing variables
		$method = str_replace("_", " ", strtolower($property));
		$method = "is".str_replace(" ", "", ucwords($method));
		
		//RETURN if it's already set
		if ($this->fireMethod( $method, $this->$property )) return false;

		//SET IT
		if (!$this->set( $property, $value )) return false;

		return true;
	}
	
	/**
	 * Street Name
	 * 
	 * @return string
	 */
	public function street_name()
	{
		return ucwords(strtolower($this->street_name));
	}
	
	/**
	 * Set Property
	 * 
	 * Method is responsible for setting the expected property with the 
	 * given value. Being a public function we must expect that the 
	 * given values are not valid. So we will need to validate them.
	 * 
	 * We never want any address information to be set that is not a
	 * correct address part.
	 * 
	 * @param string $property
	 * @param string $value
	 * @param boolean $force
	 * @return boolean
	 */
	public function set( $property = null, $value = null, $force = false )
	{
		//reasons to return
		if ( is_null($property) ) return false;
		if ( is_null($value) ) return false;
		
		//formatting for a set variable
		$method = str_replace("_", " ", strtolower($property));
		$method = "is".str_replace(" ", "", ucwords($method));
		
		//if it's not already valid
		if (!$force && !($valid = $this->fireMethod( $method, $value )) ) return false;
		
		if ($force) {
			$this->$property = $value;
		} else {
			$this->$property = $valid;
		}
		return true;
	}
	
	/**
	 * Spell Checker
	 * 
	 * This method was placed here to make the access to it that much easier
	 * 
	 * @param string $string
	 * @return string
	 */
	public function Spellchecker( $string = null )
	{
		return SpellChecker::String($string);
	}
	
	/**
	 * Return an Array
	 * 
	 * Method is designed to compile this object into an array and 
	 * return the array
	 * 
	 * @return array
	 */
	public function toArray()
	{
		//initializing variables
		$array = array(
			'street_addr' => $this->street_addr,		   
			'street_number' => $this->street_number,
			'street_name' => $this->street_name,
			'street_type' => $this->street_type,
			'direction' => $this->direction,
			'subpremise' => $this->subpremise,
			'city' => $this->city,
			'state' => $this->state,
			'postal_code' => $this->postal_code,
			'country' => $this->country
		);
		
		return $array;
	}
	
	/**
	 * ECHO
	 * 
	 * Method double checks that all values are carried through the properties
	 * and then returns the full address as a string.
	 * 
	 * @return string
	 */
	public function toString()
	{
		//formatting the full string
		$full  = $this->street_number()." ".$this->street_name()." ".$this->street_type();
		$full .= ($this->subpremise())?" ".$this->subpremise(): "";
		
		$full .= ($this->city())?', '.$this->city(): "";
		$full .= ($this->state())?', '.$this->state(): "";
		$full .= ($this->postal_code())? ' '.$this->postal_code(): "";
		$full .= ($this->country())? ' '.$this->country(): "";
		
		return (string)$full;
	}
	
	
}
?>