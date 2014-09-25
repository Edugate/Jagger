<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
By Brodie Hodges, Oct 22, 2009.
*/

/*
ajax_form_open will create a form using standard CI form_open helper function but add jQuery onsubmit js code to 
"ajaxify" the post. Only one additional optional parameter for return type to callback function over standard 
form_open().  Defaults html return type. If no id attribute is passed in, "ajax_form" is used for the form's id.
The id is used for the callback function name. My convention assumes that all callback function names as the form's
id + "_callback". You can either create your own JavaScript callback function with that name in your view, or use
one of the "simple_" helper functions below in your view to automatically create the callback function and containing
element.  The $.post() is appended to the end of the passed in onsubmit attribute value, so it is possible to do some 
pre-POST processing with JavaScript, if required.
*/
if ( ! function_exists('ajax_form_open')) {
	function ajax_form_open($action = '', $attributes = array(), $hidden = array(), $returnType = 'html') {
		$obj =& get_instance();
		$obj->load->helper('form');
		if ( ! array_key_exists('onsubmit', $attributes)) {
			$attributes['onsubmit'] = "";
		} else {
			$onsubmit = trim($attributes['onsubmit']);
			if ( ! substr($onsubmit,-1) == ";") {
				$attributes['onsubmit'] = $onsubmit . ";";
			}
		}
		if ( ! array_key_exists('id', $attributes)) {
			$attributes['id'] = "ajax_form";
		}
		$attributes['onsubmit'] .= "$.post(this.action,$('#'+this.id).serialize(),function(data,textStatus) {".$attributes['id']."_callback(data);},'".$returnType."');return false;";
		return form_open($action, $attributes, $hidden);
	}
}

/*
This function is included for aesthetics so that visually matches to the ajax_form_open in your view.  You can use 
form_close() or "</form>" if you want, instead.
*/
if ( ! function_exists('ajax_form_close')) {
	function ajax_form_close() {
		return "</form>";
	}
}

/*
function ajax_get_anchor is very similar to ajax_form_open except it creates a jQuery ajax get that uses the anchors
href attribute (generated from the $uri param).  A callback is called which is the id attribute + "_callback".  You
can either create a function called that in your view or use one of the "simple_" functions below to do that for you.
The default id is set to "ajax_get_anchor" if no id attribute is passed in to the helper, thus the default callback 
JavaScript function is "ajax_get_anchor_callback()". The $.get() is appended to the end of the passed in onclick 
attribute value, so it is possible to do some pre-GET processing with JavaScript, if required. Defaults html return
type.
*/
if ( ! function_exists('ajax_get_anchor')) {
	function ajax_get_anchor($uri = '', $title = '', $attributes = array(), $returnType = 'html') {
		$obj =& get_instance();
		$obj->load->helper('url');
		if ( ! array_key_exists('onclick', $attributes)) {
			$attributes['onclick'] = "";
		} else {
			$onclick = trim($attributes['onclick']);
			if ( ! substr($onclick,-1) == ";") {
				$attributes['onclick'] = $onclick . ";";
			}
		}
		if ( ! array_key_exists('id', $attributes)) {
			$attributes['id'] = "ajax_get_anchor";
		}
		$attributes['onclick'] .= "$.get(this.href,'',function(data,textStatus) {".$attributes['id']."_callback(data);},'".$returnType."');return false;";
		return anchor($uri,$title,$attributes);
	}
}


/*
ajax_load_anchor creates an anchor tag that will jquery ajax load the contents into the specified selector's element(s). 
$returnSelector is the where the content returned from the GET is put and can be any appropriate jQuery selector.
Per jQuery ajax load function documentation, the $optionalLoadSelector is appended to the load url and will filter
the incoming HTML document, only injecting the elements that match the $optionalLoadSelector.
$useJsCallback is a boolean that defaults to false.  If set to true the anchor id attribute + "_callback" or 
default "ajax_load_anchor_callback()" is called on return.
*/
if ( ! function_exists('ajax_load_anchor')) {
	function ajax_load_anchor($returnSelector, $optionalLoadSelector = '', $useJsCallback = false, $uri = '', $title = '', $attributes = array()) {
		$obj =& get_instance();
		$obj->load->helper('url');
		if ( ! array_key_exists('onclick', $attributes)) {
			$attributes['onclick'] = "";
		} else {
			$onclick = trim($attributes['onclick']);
			if ( ! substr($onclick,-1) == ";") {
				$attributes['onclick'] = $onclick . ";";
			}
		}
		if ($useJsCallback) {
			if (array_key_exists('id',$attributes) &&  ! $attributes['id'] === "") {
				$js_callback = $attributes['id'] . "_callback";
			} else {
				$js_callback = "ajax_load_anchor_callback";
			}
		}
		if ($optionalLoadSelector) {
			$attributes['onclick'] .= "$('".$returnSelector."').load(this.href+' ".$optionalLoadSelector . "'";
		} else {
			$attributes['onclick'] .= "$('".$returnSelector."').load(this.href";
		}
		if ($useJsCallback) {
			$attributes['onclick'] .= ",'',function(data) {".$js_callback."(data);}";
		}
		$attributes['onclick'] .= ");return false;";
		return anchor($uri,$title,$attributes);
	}
}

/*
Include this function in your view for your html return content to show in a div.  The div will
exist on initial page render and thus will be structurally in your view right where you called 
this function.  The ajax form's or ajax get anchor's id attribute value must be passed in with
the $callingElementId param.
*/
if ( ! function_exists('simple_callback_div')) {
	function simple_callback_div($callingElementId, $displayEffect = 'show("slow")', $attributes = '') {
		$obj =& get_instance();
		$obj->load->helper('form');
		$formattedAttributes = _parse_form_attributes($attributes, array());
		$simpleHtmlCallbackDiv = <<<CONTENT
			<div id="${callingElementId}_results" style="display:none;"${formattedAttributes}>
				loading ...
			</div>
			<script type="text/javascript">
				function ${callingElementId}_callback(htmlContent) {
					$("#${callingElementId}_results").html(htmlContent).${displayEffect};
				}
			</script>
CONTENT;
		return $simpleHtmlCallbackDiv;
	}
}

/*
Include this function in your view for your html return content to show in a jquery dialog div.
The dialog div is injected by jQuery and appended to the body tag. This will allow the dialog
to be opened and closed more than once.  By default, closing the dialog destroys the dialog div. 
The ajax form's or ajax get anchor's id attribute value must be passed in with the 
$callingElementId param. 
*/
if ( ! function_exists('simple_callback_dialog')) {
	function simple_callback_dialog($callingElementId, $dialogOptions = array(), $attributes = '') {
		$obj =& get_instance();
		$obj->load->helper('form');
		$formattedAttributes = _parse_form_attributes($attributes, array());
		$formattedDialogOptions = _format_jquery_options($dialogOptions);
		$simpleHtmlCallbackDialog = <<<CONTENT
			<script type="text/javascript">
				function ${callingElementId}_callback(htmlContent) {
					theDialog = $('<div id="${callingElementId}_results"${formattedAttributes}>loading ...</div>').appendTo('body');
					theDialog.html(htmlContent);
					theDialog.dialog( { ${formattedDialogOptions} } );
				}
			</script>
CONTENT;
		return $simpleHtmlCallbackDialog;
	}
}

/*
This function creates a standard text input with autocomplete functionality.
Requires that http://bassistance.de/jquery-plugins/jquery-plugin-autocomplete/ plugin is setup on your
server.  Documentation: http://docs.jquery.com/Plugins/Autocomplete
List of autocomplete options is here: 
		http://docs.jquery.com/Plugins/Autocomplete/autocomplete#url_or_dataoptions
pass in array form with $acOptions param.
$uriOrData param can be a uri string (e.g. "/user/view/") or a single dimensional array with choices
Ajax autocomplete requires a pre_system hook to function correctly.  Add to your 
application/config/hooks.php if not already there:

$hook['pre_system'][] = array(
								'class'    => 'Autocomplete',
                                'function' => 'override_get',
                                'filename' => 'autocomplete.php',
                                'filepath' => 'hooks',
                                'params'   => array()
                                );
								
Make sure autocomplete.php is in your application/hooks/ folder.
*/
if ( ! function_exists('ac_form_input')) {
	function ac_form_input($data = '', $value = '', $extra = '', $uriOrData = '', $acOptions = array() ) {
		$obj =& get_instance();
		$obj->load->helper(array('form', 'url'));
		$formattedAcOptions = _format_jquery_options($acOptions);
		if (is_string($data)) {
			$name = $data;
			unset($data);
			$data['name'] = $name;
			$data['value'] = $value;
		}
		if ( is_array($data) && (! array_key_exists('id',$data) || $data['id'] === "") ) {
			$data['id'] = 'ac_input';
		}
		$acJs = '';
		if ( is_string($uriOrData) ) {
			$url  = $uriOrData . "/";
			$acJs = <<<JS
				<script type="text/javascript">
					$(document).ready(function() {
						$('#${data['id']}').autocomplete('${url}',{ ${formattedAcOptions} });
					});
				</script>
JS;
		} elseif ( is_array($uriOrData) ) {
			$arrData = implode('|',$uriOrData);
			$acJs = <<<JS
				<script type="text/javascript">
					$(document).ready(function() {
						var staticData = "${arrData}".split("|");
						$('#${data['id']}').autocomplete(staticData,{ ${formattedAcOptions} });
					});
				</script>
JS;
		}
		$acInput = form_input($data, $value, $extra);
		return ($acJs . $acInput);
	}
}

/*
This function creates a standard textarea with autocomplete functionality.
Requires that http://bassistance.de/jquery-plugins/jquery-plugin-autocomplete/ plugin is setup on your
server.  Documentation: http://docs.jquery.com/Plugins/Autocomplete
List of autocomplete options is here: 
		http://docs.jquery.com/Plugins/Autocomplete/autocomplete#url_or_dataoptions
pass in array form with $acOptions param.
$uriOrData param can be a uri string (e.g. "/user/view/") or a single dimensional array with choices
Ajax autocomplete requires a pre_system hook to function correctly.  Add to your 
application/config/hooks.php if not already there:

$hook['pre_system'][] = array(
								'class'    => 'Autocomplete',
                                'function' => 'override_get',
                                'filename' => 'autocomplete.php',
                                'filepath' => 'hooks',
                                'params'   => array()
                                );
								
Make sure autocomplete.php is in your application/hooks/ folder.
*/
if ( ! function_exists('ac_form_textarea')) {
	function ac_form_textarea($data = '', $value = '', $extra = '', $uriOrData = '', $acOptions = array() ) {
		$obj =& get_instance();
		$obj->load->helper(array('form', 'url'));
		$formattedAcOptions = _format_jquery_options($acOptions);
		if (is_string($data)) {
			$name = $data;
			unset($data);
			$data['name'] = $name;
			$data['value'] = $value;
		}
		if ( is_array($data) && ( ! array_key_exists('id',$data) || $data['id'] === "") ) {
			$data['id'] = 'ac_textarea';
		}
		$acJs = '';
		if ( is_string($uriOrData) ) {
			$url  = $uriOrData . "/";
			$acJs = <<<JS
				<script type="text/javascript">
					$(document).ready(function() {
						$('#${data['id']}').autocomplete('${url}',{ ${formattedAcOptions} });
					});
				</script>
JS;
		} elseif ( is_array($uriOrData) ) {
			$arrData = implode('|',$uriOrData);
			$acJs = <<<JS
				<script type="text/javascript">
					$(document).ready(function() {
						var staticData = "${arrData}".split("|");
						$('#${data['id']}').autocomplete(staticData,{ ${formattedAcOptions} });
					});
				</script>
JS;
		}
		$acTextarea = form_textarea($data, $value, $extra);
		return ($acJs . $acTextarea);
	}
}

/*
This function takes a PHP array and makes it into JS array of key : value pairs.
If passing in a javascript function as a value, make sure to enclose the function
in quotes.
*/
if ( ! function_exists('_format_jquery_options')) {
	function _format_jquery_options($options = array()) {
		$formattedOptions = "";
		if (is_array($options) && count($options) > 0) {
			foreach($options as $option=>$optval) {
				if (is_bool($optval)) {
					$formattedOptions .= ' '. $option .':'.($optval ? 'true':'false').',';
				} elseif (is_int($optval)) {
					$formattedOptions .= ' '. $option .':'.$optval.',';
				} elseif (is_string($optval)) {
					if (strstr($optval, "function")) {
						$formattedOptions .= ' '.$option.':'.$optval.',';
					} else {
						$formattedOptions .= ' '.$option.':"'.$optval.'",';
					}
				}
			}
			$formattedOptions = substr($formattedOptions,0,-1);
		}
		return $formattedOptions;
	}
}
?>

