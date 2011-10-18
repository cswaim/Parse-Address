<?php
class ObjectBase
{
	/**
	 * Variable contains the error messages that the child model object has encountered.
	 * 
	 * @var array
	 */
	var $_errorMsgs = array();
    
    /**
	 * Binds a named array/hash to this object
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @access	public
	 * @param	$from	mixed	An associative array or object
	 * @param	$ignore	mixed	An array or space separated list of fields not to bind
	 * @return	boolean
	 */
	function bind( $from, $ignore=array(), $public = false )
	{
		$fromArray	= is_array( $from );
		$fromObject	= is_object( $from );

		if (!$fromArray && !$fromObject)
		{
			trigger_error( get_class( $this ).'::bind failed. Invalid from argument' );
			return false;
		}
		
		if (!is_array( $ignore )) {
			$ignore = explode( ' ', $ignore );
		}
		
		if ($fromArray) $from = array_change_key_case($from, CASE_LOWER);
		
		foreach ($this->getProperties($public) as $k => $v)
		{
			// internal attributes of an object are ignored
			if (!in_array( $k, $ignore ))
			{
				if ($fromArray && isset( $from[$k] )) 
				{
					$this->$k = $from[$k];
				} 
				else if ($fromObject && isset( $from->$k )) 
				{
					$this->$k = $from->$k;
				}
			}
		}
		return true;
	}
	
	/**
	 * Create Camel Case
	 * 
	 * Method is responsible for creating a camel case from the string given.
	 * 
	 * @param $string
	 */
	function createCamel( $string = null )
	{
		//reasons to fail
		if (is_null($string)) return false;
		
		$string = ereg_replace("[^A-Za-z0-9 _]", '', $string);
	
		
		return str_replace(" ","", ucwords(strtolower(str_replace("_", " ", $string))));
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
		$getproperty = substr($method,3);
		$property = $method;
		
		//allows use to determine what to do using the first three characters of the method call
		switch($switch)
		{
			case 'get':
				if (isset($this->$getproperty)) return $this->$getproperty;
				break;
			default:
				if (isset($this->$property)) return $this->$property;
				break;
		}
		return false;
	}
	
	/**
	 * Fire this Method
	 * 
	 * Method will determine if the requested method exists, and fire it
	 * returning a consistent boolean result or the actual result
	 * 
	 * @param $method
	 * @param $args
	 * @return boolean
	 */
	function fireMethod( $method = null, $args = null )
	{
		//reasons to fail
		if (!method_exists($this, $method)) return false;
		
		//run the method
		$result = $this->$method( $args );
		
		//making the results consistent
		if (is_null($result)) return false;
		if (!$result) return false;
		
		return $result;
	}
	
	/**
	 * Returns a property of the object or the default value if the property is not set.
	 *
	 * @access	public
	 * @param	string $property The name of the property
	 * @param	mixed  $default The default value
	 * @return	mixed The value of the property
	 * @see		getProperties()
	 * @since	1.5
 	 */
	function get( $property, $default = null )
	{
		//initializing variables
		$result = false;
		if ( !($result = $this->fireMethod( $property, $default )) && isset($this->$property) )
		{
			$result = $this->$property;
		}
		
		if($result)
			return $result;
		return $default;
	}
	
	/**
     * Get Model Errors
     * 
     * Method will return a false if the error array is empty or will return 
     * the error messages.
     * 
     * @return string
     */
    function getErrors()
    {
    	//reasons to fail
    	if (!isset($this->_errorMsgs)) return false;
    	if (empty($this->_errorMsgs)) return false;
    	
    	return $this->_errorMsgs;
    }
    
	/**
	 * Get Instance
	 * 
	 * Method returns an instance of the proper class and its variable set
	 * 
	 * @param $class string
	 */
	public static function &getInstance( $class, $options = null )
	{
		//intialize variables
		static $instance;
		$appendix = "vendor";
		
		if (is_null($instance))
		{
			$instance = array();
		}
		
		//create the class if it does not exist
		if (!isset($instance[$class]))
		{
			//creating the instance
			//initializing variables
			$class = parent::createCamel($class."_".$appendix);
			s_autoload($class.$appendix);
			
			$instance[$class] = new $class($class, $options);
		}
		
		//return an instance of this instantiation
		return $instance[$class];
	}
    
    /**
	 * Get this Class Methods
	 * 
	 * Method will create an array of this classes methods, allowing 
	 * the programmer to filter out unwanted methods from the array.
	 * 
	 * @param $prefix
	 * @param $private
	 */
	function getMethods( $prefix = null, $private = false )
	{
		//initializing variables
		$methods = get_class_methods($this);
		if (!is_null($prefix))
		{
			$prelen = strlen($prefix);
			
			if (substr($prefix,0,1) == '_') $private = true;
		}
		
		foreach ($methods as $key => $method)
		{
			//remove the private methods
			if (!$private)
			{
				if (substr($method,0,1) == '_') unset($methods[$key]);
			}
			
			//remove the methods that are not prefixed properly
			if (!is_null($prefix))
			{
				if (substr($method,0,$prelen) != $prefix) unset($methods[$key]);
			}
		}
		
		return $methods;
	}

	/**
	 * Returns an associative array of object properties
	 *
	 * @access	public
	 * @param	boolean $public If true, returns only the public properties
	 * @return	array
 	 */
	function getProperties( $public = true )
	{
		$vars  = get_object_vars($this);

        if($public)
		{
			foreach ($vars as $key => $value)
			{
				if ('_' == substr($key, 0, 1)) {
					unset($vars[$key]);
				}
			}
		}

        return $vars;
	}
	
	/**
	 * Is this property set?
	 * 
	 * Method will check the value for 
	 * 
	 * @param $property
	 * @return boolean
	 */
	function _isset( $property = null )
	{
		//reasons to fail
		if (!($this->$property)) return false;
		if (is_object($this->$property)) return true;
		if (is_array($this->$property) && empty($this->$property)) return false;
		if (strlen(trim($this->$property)) < 1) return false;
			
		return true;
	}
	
	/**
	 * Is this Valid
	 * 
	 * Method will search for all of the _valid methods and then loop
	 * through each of them, returning true only if all methods return
	 * true.
	 * 
	 * @return boolean
	 */
	public function isValid( $method_prefix = '_valid' )
	{
		//initializing variables
		$validation_methods = $this->getMethods( $method_prefix );
		$valid = true;
		
		//checking the boolean response from each method
		//once false, it cannot be set to true
		foreach ($validation_methods as $method)
		{
			if ($valid && !$this->$method()) $valid = false;
		}
		
		return $valid;
	}
	
	
	
	/**
	 * Modifies a property of the object, creating it if it does not already exist.
	 *
	 * @access	public
	 * @param	string $property The name of the property
	 * @param	mixed  $value The value of the property to set
	 * @return	mixed Previous value of the property
	 * @see		setProperties()
	 */
	function set( $property, $value = null )
	{
		$previous = isset($this->$property) ? $this->$property : null;
		$this->$property = $value;
		return $previous;
	}

	/**
     * Set Error
     * 
     * Method records all of the errors that this table model has encoutered.
     * 
     * @param $error
     */
    function setError( $error = "" )
    {
    	//reasons to fail
    	if (trim($error) == "") return false;
    	
    	//initializing variables
    	if (!isset($this->_errorMsgs))
    	{
    		$this->_errorMsgs = array();
    	}
    	
    	$this->_errorMsgs[] = $error;
    	
    	return true;
    }
    
    /**
	* Set the object properties based on a named array/hash
	*
	* @access	protected
	* @param	$array  mixed Either and associative array or another object
	* @return	boolean
	*/
	function setProperties( $properties )
	{
		$properties = (array) $properties; //cast to an array

		if (is_array($properties))
		{
			foreach ($properties as $k => $v) {
				$this->$k = $v;
			}

			return true;
		}

		return false;
	}
}
?>