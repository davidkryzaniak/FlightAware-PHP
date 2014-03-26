<?php

/**
 * Class FlightAware
 *
 * A simple (magical) class for interacting with FlightAware's API
 */
class FlightAware {

    private $config;

    /**
     * Simply initialization.
     *
     * @param mixed $ini    Optional path to the config file if not in the same directory or array of settings OR
     *                      an array of settings like this:
     *
     *              array("username" => "","apiKey" => "","requestURL" => "flightxml.flightaware.com/json/FlightXML2/");
     *
     * @throws Exception 'FlightAware: Configuration File Missing!' When the script can't find the
     * flightaware.config.ini or no config array is set
     */
    public function __construct($ini = "flightaware.config.ini")
    {
        if(is_array($ini)){
            $this->config = $ini;
        }elseif(file_exists($ini)){
            $this->config = parse_ini_file($ini);
        }else{
            throw new Exception('FlightAware: Configuration File Missing!');
        }
    }

    /**
     * The actual heavy lifter
     *
     * @param string $function      Name of the flightaware api call
     * @param array $parameters     Key/Value pair of the parameters
     *
     * @return string JSON results from flightaware OR
     */
    public function request($function, array $parameters)
    {
        $options = array(
            'http' =>
                array(
                    'method'  => 'POST',
                    'header'  => 'Content-type: application/x-www-form-urlencoded',
                    'content' => http_build_query($parameters)
                )
        );

        $context  = stream_context_create($options);
        $url = "http://".$this->config['username'].":".$this->config['apiKey']."@".
            $this->config['requestURL'].$function;

        $results = file_get_contents($url, FALSE, $context);

        if(in_array('HTTP/1.1 200 OK',$http_response_header)){
            return json_decode($results,TRUE);
        }else{
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
