<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * GeSHi Library for CodeIgniter
 *
 * This is a library for CodeIgniter to implement syntax highlighting
 * for CodeIgniter using the popular GeSHi Syntax Highlighter.
 * 
 * This file should be named "geshilib.php" (all lower-case), and go into your 
 * "application/libraries" directory.  For more information and installation
 * instructions, refer to the link below.
 * 
 * @author	Casey McLaughlin
 * @link		http://github.com/caseyamcl/geshiforci
 * @version 2.0
 */

//Load this before loading class, so GeSHi constants will be available
//by the time this class runs.
include_once(dirname(__FILE__) . '/geshi/geshi.php'); //Load constants

class Geshilib
{
	protected $ci;
	protected $params = array();

	/**
	 * @var $fail_gracefully  Fail gracefully if GeSHi highlighting fails?
	 */
	protected $fail_gracefully = TRUE;
	
	/**
	 * @var $default_language  Which language to highlight in if non specified (GeSHi will complain without this)
	 */
	protected $default_language = 'xml';
	
	/**
	 * @var array A private value that contains the parameters for a current task
	 */
	protected $current_params;
		
  // ------------------------------------------------------------------------
	
  /**
   * Constructor
   *
   * @access public
   * @return Geshilib
   */
	function __construct(array $params = array())
	{	  
	  //Get access to CI library
		$this->ci =& get_instance();
				  
		//Load params
		$this->params = $params;
		
	}

	// ------------------------------------------------------------------------	

	/**
	 * Simply return the geshi version
	 * 
	 * @return string
	 */
	public function get_geshi_version()
	{
		return GESHI_VERSION;
	}
	
  // ------------------------------------------------------------------------	

	/**
	 * Highlight some code using GeSHi
	 * 
	 * @param string $code
	 * @param string $language
	 * @param array $params
	 * @return string
	 */
	public function highlight($code, $language = NULL, $params = array())
	{
		//Prepare the paramaters
		$this->_prepare_params($params);
		
		if (is_null($language))
			$language = $this->default_language;
		
		//Create a new GeSHi Object
		$go = new GeSHi($code, $language);
		
		//Apply the parameters
		$go = $this->_apply_geshi_params($params, $go);
		
		//Render and Return
		$result = $go->parse_code();
		
		//If not fail_gracefully, report on the error here
		if ( ! $this->fail_gracefully && $go->error())
			throw new Exception($go->error());
		
		return $result;		
	}	
	
  // ------------------------------------------------------------------------	
	
  /**
   * Find and convert GeSHi output inside of a string of HTML code
	 * 
   * Parse all <sourcecode>...</sourcecode> tags in the CI output and converts
   * them to GeSHi syntax-highlighted code
   * 
   * @param str $str
   * @param array $params
   */
	public function filter($str, $params = NULL)
	{	
		//If no params sent, use class properties
		$this->current_params = $params ?: $this->params;
								
		//Use preg_replace_callback() to replace the output
		$regex = "/<sourcecode(\s?language='(.+?)'\s?)?>(.+?)<\/sourcecode>/si";
		$str = preg_replace_callback($regex, array($this, '_filter_callback'), $str);
				
		//Reset the current_params
		$this->current_params = NULL;
		
		return $str;
	}
	
  // ------------------------------------------------------------------------	
	
	/**
	 * Override CI Output, adding Syntax Highlighting for <sourcecode> blocks
	 * 
	 * Runs the GeSHi <sourcecode>...</sourecode> filter on CI output, and
	 * optionally displays the output using CI's output class
	 * 
	 * Use this function if you want to use the display_override hook
	 * 
	 * @param array $params 
	 * @param boolean $display
	 */
	public function geshi_display_override($params = NULL, $display = TRUE)
	{
    //Get the output from CI, run the filter, and reset it
		$ci_output = $this->ci->output->get_output();
		$ci_output = $this->filter($ci_output, $params);
		$this->ci->output->set_output($ci_output);
		
		//Display it!
		if ($display)
			$this->ci->output->_display();
	}

	// ------------------------------------------------------------------------	
	
	private function _filter_callback($arr)
	{
		//$arr[2] is the language
		//$arr[3] is the code
		
		if (empty($arr[2]))
			$arr[2] = NULL;
		
		return $this->highlight($arr[3], $arr[2], $this->current_params);
	}
		
  // ------------------------------------------------------------------------	
	
	/**
	 * Apply the parameters
	 * 
	 * Maps parameters from the array to geshi methods
	 * 
	 * @param array $params
	 * @param GeSHi $geshi_object
	 */
	private function _apply_geshi_params($params, $geshi_object)
	{
		foreach($params as $k => $v)
		{
			if ( ! is_numeric($k))
			{
				//If the value is not an array, convert it to one
				$v = (is_array($v)) ? $v : array($v);
				
				list($mthd, $args) = array($k, $v);
			}
			else
				list($mthd, $args) = array($v, array());
			
			//If necessary, convert constants that were sent as strings
			//(which may happen if calling this library from a hook)
			foreach($args as &$arg)
			{
				if (is_string($arg) && strtoupper($arg) == $arg && defined($arg))
					$arg = constant($arg);
			}
			
			call_user_func_array(array($geshi_object, $mthd), $args);
		}
		
		return $geshi_object;
	}
	
  // ------------------------------------------------------------------------	

	/**
	 * Prepare the paramaters
	 * 
	 * Basically, this just looks for, reads, and unsets any paramaters
	 * that are important to this class, but not to GeSHi.  After this
	 * is done, the output will contain only parameters that translate 
	 * into GeSHi object methods.
	 * 
	 * @param array $params 
	 * @return array
	 */
	private function _prepare_params($params)
	{
		//Things to read and remove from the array
		$mappings = array(
			'fail_gracefully',
			'default_language'
		);
		
		//Other things that should not be in the array
		$illegals = array(
			'parse_code', 'error', 'get_stylesheet'
		);
				
		//Remove the mapped items
		foreach($mappings as $mapping)
		{
			if (isset($params[$mapping]))
			{
				$this->$mapping = $params[$mapping];
				unset($params[$mapping]);
			}
		}		
		
		//Remove illegal values
		foreach($illegals as $illegal)
		{
			if (isset($params[$illegal]))
			{
				if ( ! $this->fail_gracefully)
					throw new Exception("Cannot use '$illegal' paramter in geshilib!");				
				unset($params[$illegal]);
			}
		}		
		
		return $params;
	}
}

/* EOF: geshi.php */
