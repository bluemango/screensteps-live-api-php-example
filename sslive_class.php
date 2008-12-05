<?php

// Version 0.8.0.1

// You need to get this from PEAR
// http://pear.php.net/package/Crypt_HMAC

## Use this line if you put the HMAC.php file in the same directory
require_once(dirname(__FILE__) . '/HMAC.php');
## Use the following line if you keep HMAC.php in the PEAR repository.
//require_once('Crypt/HMAC.php');


class SSLiveAPI {
	// PUBLIC
	var $domain = '';
	var $api_key = '';
	var $last_error = '';
	var $protocol = 'http';
	var $show_protected = false;
	var $use_simplexml = false;
	
	// PRIVATE
	var $xml_depth = 0;
	var $xml_node_arrays = NULL;
	var $xml_current_tag = NULL;
	var $last_depth_closed = 0;
	var $xml_doc_type = '';
	
	// PHP 4
	function SSLiveAPI($domain, $api_key, $protocol='http') {
		$this->__construct($domain, $api_key, $protocol);
	}
	
	function __construct($domain, $api_key, $protocol='http') {
		$this->domain = $domain;
		$this->api_key = $api_key;
		$this->protocol = $protocol;
	}
	
	function __destruct() {
	
	}
	
	// PUBLIC
	
	function GetSpaces() {
		// Example URL: http://example.screensteps.com/spaces
		$data = '';

		$this->last_error = $this->requestURLData($this->getCompleteURL('/spaces/'), $data);
		if ($this->last_error == '') {
			if ($this->use_simplexml)
				return simplexml_load_string($data);
			else
				return $this->XMLToArray($data, 'spaces');
		} else {
			return NULL;
		}
	}
	
	function GetSpace($space_id) {
		// Example URL: http://example.screensteps.com/spaces/id
		$data = '';
		
		$this->last_error = $this->requestURLData($this->getCompleteURL('/spaces/'. $space_id), $data);
		if ($this->last_error == '') {
			if ($this->use_simplexml)
				return simplexml_load_string($data);
			else
				return $this->XMLToArray($data, 'space');
		} else {
			return NULL;
		}
	}
	
	function GetManual($space_id, $manual_id) {
		// Example URL: http://example.screensteps.com/spaces/ID/manuals/ID
		$data = '';
		
		$this->last_error = $this->requestURLData($this->getCompleteURL('/spaces/' . $space_id . '/manuals/'. $manual_id), $data);
		if ($this->last_error == '') {
			if ($this->use_simplexml)
				return simplexml_load_string($data);
			else
				return $this->XMLToArray($data, 'manual');
		} else {
			return NULL;
		}
	}
	
	function GetBucket($space_id, $bucket_id) {
		// Example URL: http://example.screensteps.com/spaces/ID/buckets/ID
		$data = '';
		
		$this->last_error = $this->requestURLData($this->getCompleteURL('/spaces/' . $space_id . '/buckets/'. $bucket_id), $data);
		if ($this->last_error == '') {
			if ($this->use_simplexml)
				return simplexml_load_string($data);
			else
				return $this->XMLToArray($data, 'bucket');
		} else {
			return NULL;
		}
	}
	
	function GetManualLesson($space_id, $manual_id, $lesson_id) {
		// Example URL: http://example.screensteps.com/spaces/ID/manuals/ID/lessons/ID
		$data = '';
		
		$lesson_id = intval($lesson_id);
		$this->last_error = $this->requestURLData($this->getCompleteURL('/spaces/' . $space_id . '/manuals/'. $manual_id . '/lessons/' . $lesson_id), $data);
		if ($this->last_error == '') {
			if ($this->use_simplexml)
				return simplexml_load_string($data);
			else
				return $this->XMLToArray($data, 'lesson');
		} else {
			return NULL;
		}
	}
	
	
	function GetBucketLesson($space_id, $bucket_id, $lesson_id) {
		// Example URL: http://example.screensteps.com/spaces/ID/buckets/ID/lessons/ID
		$data = '';
		
		$lesson_id = intval($lesson_id);
		$this->last_error = $this->requestURLData($this->getCompleteURL('/spaces/' . $space_id . '/buckets/'. $bucket_id . '/lessons/' . $lesson_id), $data);
		if ($this->last_error == '') {
			if ($this->use_simplexml)
				return simplexml_load_string($data);
			else
				return $this->XMLToArray($data, 'lesson');
		} else {
			return NULL;
		}
	}
	
	
	// PRIVATE
	
	function getCompleteURL($request) {
		$url = $this->protocol . '://' . $this->domain . $request;
		if ($this->show_protected) $url .= '?show_protected=true';
		return $url;
	}
	
	function requestURLData($url, &$data) {
		$parsed_url = parse_url($url);
		$path_query = $parsed_url['path'];
		if ($this->show_protected) $path_query .= '?show_protected=true';
		$httpDate = gmdate("D, d M Y H:i:s T");

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
				
		## Build authentication header
		$header[] = "Content-Type: application/xml";
		$header[] = "Accept: application/xml";
		$header[] = "Date: " . $httpDate;
		$header[] = "Authorization: " . $this->encode($this->domain . ':' . $path_query . ':' . $httpDate);
		
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		
		$data = curl_exec($curl);
		$error = curl_error($curl);
		curl_close($curl);
		
		if (strcmp($data, "Couldn't authenticate you") == 0)
			$error = 'bad authentication';
			
		return $error;
	}

	function encode($data) {
		$hasher =& new Crypt_HMAC($this->api_key, "sha1");
		$digest = $hasher->hash($data);
		// hash_mac isn't installed on two systems I tried so we use PEAR library
		// $digest = hash_mac("sha1", $data, $this->api_key, true);
		return base64_encode(pack('H*', $digest));
	}
	
	
	// No SimpleXML in PHP 4...
	function XMLToArray($data, $type) {
		// print_r ($data);
		
		// Create an configure
		$parser = xml_parser_create('UTF-8');

		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1); 
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0); 
		xml_set_object($parser, $this);
		
		// Register callbacks
		xml_set_element_handler($parser, 'tag_open', 'tag_close');
		xml_set_character_data_handler($parser, 'character_data');
		
		// Initialize variables
		$array = array();
		$this->xml_node_arrays = array();
		$this->xml_current_tag = array();
		$this->xml_depth = -1;
		$this->last_depth_closed = -1;
		$this->xml_doc_type = $type;
		
		// Parse XML
		xml_parse($parser, $data, TRUE);
		xml_parser_free($parser);
		
		// Now point array that is returned at the proper dimension of the array.
		if (isset($this->xml_node_arrays[0])) 
		{
			switch ($type) {
				case 'spaces':
					$array = $this->xml_node_arrays[0]['spaces'];
					break;
				case 'space':
					$array = $this->xml_node_arrays[0]['space'];
					break;
				/*case 'manuals':
					$array = $this->xml_node_arrays[0]['manuals'];
					break;*/
				case 'manual':
					$array = $this->xml_node_arrays[0]['manual'];
					break;
				case 'bucket':
					$array = $this->xml_node_arrays[0]['bucket'];
					break;
				case 'lesson':
					$array = $this->xml_node_arrays[0]['lesson'];
					break;
			}
		}
		
		// cleanup
		$this->xml_node_arrays = NULL;
		$this->xml_current_tag = NULL;
		$this->xml_doc_type = '';
		
		//print_r($array);
		return $array;
	}
	
	function tag_open($parser, $tagName, $attributes) 
	{
		$this->xml_depth++;
		$this->xml_current_tag[$this->xml_depth] = $tagName;
	}
	
	function tag_close($parser, $tagName)
	{
		//print '$tagName: ' . $tagName . "\n";
		
		if ($this->last_depth_closed >= 0 && $this->xml_depth < $this->last_depth_closed) {
			
			// Closing level. Store array.
			$parentTagName = $this->xml_current_tag[$this->xml_depth];
			$storeAsArrayIndex = TRUE;
			
			// Determine which nodes are stored as indexes and which are stored as simple keyed arrays.
			// print '$parentTagName: ' . $parentTagName . "\n";
			switch ($this->xml_doc_type) {
				case 'spaces':
					switch ($parentTagName) {
						case 'spaces':
							$storeAsArrayIndex = FALSE;
							break;
					}
					break;
					
				case 'space':
					switch ($parentTagName) {
						case 'space':
						case 'assets':
							$storeAsArrayIndex = FALSE;
							break;
					}
					break;
					
				/*case 'manuals':
					switch ($parentTagName) {
						case 'manuals':
							$storeAsArrayIndex = FALSE;
							break;
					}
					break;*/
				
				case 'manual':
					switch ($parentTagName) {
						case 'manual':
						case 'space':
						case 'chapters':
						case 'lessons':
						case 'tags':
							$storeAsArrayIndex = FALSE;
							break;
					}
					break;
				
				case 'bucket':
					switch ($parentTagName) {
						case 'bucket':
						case 'lessons':
						case 'tags':
							$storeAsArrayIndex = FALSE;
							break;
					}
					break;
				
				case 'lesson':
					switch ($parentTagName) {
						case 'lesson':
						case 'manual':
						case 'bucket':
						case 'space':
						case 'steps':
						case 'next_lesson':
						case 'previous_lesson':
							$storeAsArrayIndex = FALSE;
							break;
					}
					break;
			}
			
			// Store array one level up in parent
			if ($storeAsArrayIndex === TRUE)
				$this->xml_node_arrays[$this->xml_depth][$parentTagName][] = $this->xml_node_arrays[$this->xml_depth + 1];
			else
				$this->xml_node_arrays[$this->xml_depth][$parentTagName] = $this->xml_node_arrays[$this->xml_depth + 1];
			
			// Reset array for previous level
			$this->xml_node_arrays[$this->xml_depth + 1] = array();
		}
		else
		{
			// Make sure node exists for tag. Empty nodes won't call character_data			
			if (!isset($this->xml_node_arrays[$this->xml_depth][$tagName])) {
				//print "not set: " . $tagName . "\n";
				$this->xml_node_arrays[$this->xml_depth][$tagName] = '';
			}
			
		}
		
		$this->last_depth_closed = $this->xml_depth;
		
		$this->xml_current_tag[$this->xml_depth] = '';
		$this->xml_depth--;
	}
	
	// Stores text of current node
	function character_data($parser, $string) {
		//print 'tag name: ' . $this->xml_current_tag[$this->xml_depth] . "\n";
		if (trim($string) != '') {
			$tagName = $this->xml_current_tag[$this->xml_depth];
			// Avoid 'notices' by defining elements
			if (!isset($this->xml_node_arrays[$this->xml_depth])) $this->xml_node_arrays[$this->xml_depth] = array();
			if (!isset($this->xml_node_arrays[$this->xml_depth][$tagName])) $this->xml_node_arrays[$this->xml_depth][$tagName] = '';
			$this->xml_node_arrays[$this->xml_depth][$tagName] .= $string;
		}
	}
}

?>