<?php

namespace FlightAwareAPI;

/**
 * Class FlightAware
 *
 * @author davidkryzaniak
 * @version 1.1.0
 *
 * A simple (magical) class for interacting with FlightAware's API
 */
class Client {

    private $config = array("username" => NULL,"apiKey" => NULL,"requestURL" => NULL, 'requestProtocol' => NULL);

	public $lastRequestRaw = NULL;
	public $lastRequestHeaderRaw = NULL;
	public $lastRequestURL = NULL;
	public $lastRequestSuccess = NULL;

    /**
     * Simply initialization.
     *
     * @param array $config Optional path to the config file if not in the same directory or array of settings OR
     *      an array of settings like this:
     *
     *      array("username" => "***","apiKey" => "***","requestURL" => "flightxml.flightaware.com/json/FlightXML2/");
     */
    public function __construct($config)
    {
        if(is_array($config)){
            $this->config = $config;
        }else{
            //user is going to manually define the info using the setter functions
        }
    }

	/**
	 * @param $username string      Your FlightAware Username
	 *
	 * @return $this Object         Used for fluent interface chained calls
	 */
	public function setUsername($username)
	{
		$this->config['username'] = $username;
		return $this;
	}

	/**
	 * @param $apiKey string    Your FlightAware API Key
	 *
	 * @return $this Object     Used for fluent interface chained calls
	 */
	public function setApiKey($apiKey)
	{
		$this->config['apiKey'] = $apiKey;
		return $this;
	}

	/**
	 * @param $url string           Your FlightAware API URL
	 * @param $protocol string      The protocol for the API URL. Should be https:// or http://
	 *
	 * @return $this Object     Used for fluent interface chained calls
	 */
	public function setRequestURL($url, $protocol = 'http://')
	{
		$this->config['requestURL'] = $url;
		$this->config['requestProtocol'] = $protocol;
		return $this;
	}

	/**
	 * Before making a request, we need to make sure we have all the pieces.
	 *
	 * @return bool         True if checks pass
	 *
	 * @throws \Exception    We can't make the call without these items.
	 */
	private function _doCheckCredentials()
	{
		if(!isset($this->config['username']) || strlen($this->config['username']) > 3 ){
			throw new \Exception('Missing or Invalid FlightAware Username');
		}elseif(!isset($this->config['apiKey']) || strlen($this->config['apiKey']) > 3 ){
			throw new \Exception('Missing or Invalid FlightAware API Key');
		}elseif(
			!isset($this->config['requestURL']) || !isset($this->config['requestProtocol']) ||
			filter_var($this->config['requestProtocol'].$this->config['requestURL'],FILTER_VALIDATE_URL) === FALSE
		){
			throw new \Exception('Missing or Invalid FlightAware Request URL/Protocol');
		}
		return TRUE;
	}

    /**
     * The actual heavy lifter
     *
     * @param string $function      Name of the flightaware api call
     * @param array $parameters     Key/Value pair of the parameters
     *
     * @return string JSON results from flightaware OR array with the key set to "error"
     */
    public function request($function, array $parameters)
    {
	    $this->_doCheckCredentials();

        $options = array(
            'http' =>
                array(
                    'method'  => 'POST',
                    'header'  => 'Content-type: application/x-www-form-urlencoded',
                    'content' => http_build_query($parameters)
                )
        );

        $context  = stream_context_create($options);
        $url = $this->config['requestProtocol'].$this->config['username'].":".$this->config['apiKey']."@".
            $this->config['requestURL'].$function;

        $this->lastRequestRaw = $results = file_get_contents($url, FALSE, $context);
	    $this->lastRequestHeaderRaw = $http_response_header;
	    $this->lastRequestURL = $url;
	    $this->lastRequestSuccess = TRUE;

        if(in_array('HTTP/1.1 200 OK',$http_response_header)){
            return json_decode($results,TRUE);
        }else{
	        $this->lastRequestSuccess = FALSE;
            return array('error'=>$http_response_header);
        }
    }

    /**
     * Magic calls "$instantiatedFlightAware->[API method name]" for requests
     *
     * @param string $function      Called method name
     * @param array $parameters     Arguments passed to the method
     * @return array                Decoded results from API
     */
    public function __call($function, $parameters) {
        return $this->request($function, $parameters[0]);
    }
}
