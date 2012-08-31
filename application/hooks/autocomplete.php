<?php

/*
By Brodie Hodges, Oct. 22, 2009.
*/

if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
* 	Make sure this file is placed in your application/hooks/ folder.
*
*	jQuery autocomplete plugin uses query string.  Autocomplete class slightly modified from excellent blog post here:
*	http://czetsuya-tech.blogspot.com/2009/08/allowing-url-query-string-in.html 
*	Ajax autocomplete requires a pre_system hook to function correctly.  Add to your 
*	application/config/hooks.php if not already there:

	$hook['pre_system'][] = array(
								'class'    => 'Autocomplete',
								'function' => 'override_get',
                                'filename' => 'autocomplete.php',
                                'filepath' => 'hooks',
                                'params'   => array()
                                );
								
*								
* 
*/
class Autocomplete {
	function override_get() {
		if (strlen($_SERVER['QUERY_STRING']) > 0) {
			$temp = @array();
			parse_str($_SERVER['QUERY_STRING'], $temp);
			if (array_key_exists('q', $temp) && array_key_exists('limit', $temp) && array_key_exists('timestamp', $temp)) {
				$_POST['q'] = $temp['q'];
				$_POST['limit'] = $temp['limit'];
				$_POST['timestamp'] = $temp['timestamp'];
				$_SERVER['QUERY_STRING'] = "";
				$_SERVER['REDIRECT_QUERY_STRING'] = "";
				$_GET = @array();
				$url = strpos($_SERVER['REQUEST_URI'], '?');
				if ($url > -1) {
					$_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 0, $url);
				}
			}
		}
	}
}
?>