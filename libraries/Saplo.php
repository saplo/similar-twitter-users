<?php
/**
 * Saplo API
 * 
 * @author    Joakim Stenberg <joakim@saplo.com>
 * @author    Oskar Olsson <oskar@saplo.com>
 * @author    Fredrik Hörte <fredrik@saplo.com>
 * @copyright 2011 Saplo AB
 */
class Saplo
{
	private $_api_key;
	private $_secret_key;
	private $_attempt = 1;
	private $_endpoint = 'https://api.saplo.com/rpc/json';
	private $_token;
	private $_json_request;
	
	/**
	 * Constructor
	 * 
	 * @access public
	 */
	public function __construct($api_key, $secret_key)
	{
		$this->_api_key    = $api_key;
		$this->_secret_key = $secret_key;

		$this->connect();
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Connect to Saplo API
	 * 
	 * @return void
	 * 
	 * @throws SaploException
	 * 
	 * @access public
	 */
	public function connect()
	{
		$params = array(
			'api_key'    => $this->_api_key,
			'secret_key' => $this->_secret_key
		);
		
		$result = $this->request('auth.accessToken', $params);
		
		$this->_token = $result['access_token'];
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Perform an API request
	 * 
	 * @param string $method
	 * @param array  $params
	 * @param string $trim
	 * @param int    $id
	 * 
	 * @return array
	 * 
	 * @throws SaploException
	 * 
	 * @access public
	 */
	public function request($method, $params = array(), $trim = '', $id = 0)
	{
		$request = array(
			'method'  => $method,
			'params'  => $params,
			'id'	  => $id,
			'jsonrpc' => '2.0'
		);
		
		$this->_json_request = json_encode($request);
		$json_response = $this->post($this->_json_request);
		
		return $this->parse_response($json_response, $trim);
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Perform multiple API requests at once
	 * 
	 * @param array $requests
	 * 
	 * @return string
	 * 
	 * @access public
	 */
	public function batch_requests($requests)
	{
		$this->_json_request = json_encode($requests);
		$json_response = $this->post($this->_json_request);
		
		return $this->parse_response($json_response);
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Post data using cURL
	 * 
	 * @param string $json_request
	 * 
	 * @return string
	 * 
	 * @access private
	 */
	private function post($json_request)
	{
		Debug::msg($json_request, 'JSON-request');
		
		$post_url = $this->_endpoint . '?access_token=' . $this->_token;
		
		// Get length of post
		$postlength = strlen($json_request);
		
		// Open connection
		$ch = curl_init();
		
		// Set the url, number of POST vars, POST data
		curl_setopt($ch, CURLOPT_URL, $post_url);
		curl_setopt($ch, CURLOPT_POST, $postlength);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json_request);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		$json_response = curl_exec($ch);
		
		// Close connection
		curl_close($ch);
		
		Debug::msg($json_request, 'JSON-response');
		
		return $json_response;
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Parse API response
	 * 
	 * @param string $json_response
	 * @param string $trim
	 * 
	 * @return array
	 * 
	 * @throws SaploException
	 * 
	 * @access private
	 */
	private function parse_response($json_response, $trim = '')
	{
		$response = json_decode($json_response, true);
		
		/*
		 * If $response['id'] is set then this is the response of a single call.
		 * If $response['error'] is set then error has occured with single call.
		 * If $response is not set, then no response was returned from Saplo.
		 * Else this is the response of a batch call and we should just return
		 * the decoded response (error or no error).
		 */
		if (isset($response['id']))
		{
			if (isset($response['result']))
			{
				$response = $response['result'];
				
				if ($trim)
				{
					$response = $this->trim($trim, $response);
				}
			}
			else if (isset($response['error']))
			{
				// If request failed because the access token has expired, then we try to
				// reconnect to the API and then run request one more time.
				if ($response['error']['code'] == 595 AND $this->_attempt < 2)
				{
					return $this->retry_request($this->_json_request, $trim);
				}
				else
				{
					throw new SaploException($response['error']['msg'], $response['error']['code'], $json_response);
				}
			}
			else
			{
				throw new SaploException('Did not receive response Error or Result from Saplo API.', 0, $json_response);	
			}
			
			// Reset API call attempt counter
			$this->_attempt = 1;
			
			return $response;
		}
		else if ($response === null)
		{
			// If no response was returned from Saplo API then we try to reconnect to
			// the API and then run request one more time.
			if ($this->_attempt < 2)
			{
				return $this->retry_request($this->_json_request, $trim);
			}
			else
			{
				// We didn't get any response from Saplo API at all. This might mean that
				// API servers are unavailable.
				throw new SaploException('No response from Saplo API', 0, $this->_json_request);
			}
		}
		else
		{
			return $response;
		}
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Try reconnecting to Saplo API and retry making request that was
	 * failing due to lost contact with Saplo API.
	 * 
	 * @param string $json_request
	 * @param string $trim
	 * 
	 * @return array
	 * 
	 * @access private
	 */
	private function retry_request($json_request, $trim)
	{
		$this->_attempt++;
		
		$this->connect();
		$json_response = $this->post($json_request);
		
		return $this->parse_response($json_response, $trim);
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Return value of specified key in array
	 * 
	 * @param string $param
	 * @param array  $array
	 * 
	 * @return mixed
	 * 
	 * @access private
	 */
	private function trim($key, $array)
	{
		return $array[$key];
	}
}

// ----------------------------------------------------------------------

/**
 * Saplo API Exceptions
 * 
 * @author    Fredrik Hörte <fredrik@saplo.com>
 * @author    Joakim Stenberg <joakim@saplo.com>
 * @copyright 2011 Saplo AB
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class SaploException extends Exception
{
	private $_json_request;
	
	/**
	 * Constructor
	 * 
	 * @param string $message
	 * @param int    $code
	 * @param string $json_request JSON request that caused the error
	 * @param object $previous
	 * 
	 * @return void
	 * 
	 * @access public
	 */
	public function __construct($message, $code = 0, $json_request = null, Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
		$this->_json_request = $json_request;
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Get JSON request
	 * 
	 * @param string $param
	 * @param array  $array
	 * 
	 * @return mixed
	 * 
	 * @access public
	 */
	public function get_json_request()
	{
		return $this->_json_request;
	}
}
