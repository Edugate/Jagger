<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2012, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * MY_form_validation Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class MY_form_validation extends CI_form_validation {

    protected $em;

    function __construct()
    {
        parent::__construct();
        $this->em = $this->CI->doctrine->em;
        $this->CI->load->helper('metadata_elements');
    }

    /*
      function is_unique($attribute_name, $model, array $condition) {
      $cond = array_keys($condition);

      $ent = $this->em->getRepository("$model")->findOneBy($condition);
      if (!empty($ent)) {
      $this->set_message($attribute_name, "The %s : \"" . $condition[$cond[0]] . "\" does already exist in the system.");
      return FALSE;
      } else {
      return TRUE;
      }
      }
     */


    function str_matches_array( $str, $ar)
    {
       $result = false;
       $ar = unserialize($ar);
       if(empty($str))
       {
          if(count($ar) == 0)
          {
             $result = true;
          }
       }
       else
       {
           $ar1 = explode(",",$str);
           if(count(array_diff($ar1,$ar))==0 && count(array_diff($ar,$ar1))==0)
           {
              $result = true;
           }
           
       }
       if(!$result)
       {
           $this->set_message('str_matches_array', 'The %s  must not been changed to '.htmlentities($str));
       }
       return $result;
    }
    function matches_value($str1,$str2)
    {
         log_message('debug','GKS '.__METHOD__.' '.$str1 .' :: '.$str2);
         if(strcmp($str1,$str2) === 0)
         {
             return TRUE;
         }
         else
         {
             $this->set_message('matches_value','The %s: '.htmlentities($str2).' must not been changed to '.htmlentities($str1));
             return  FALSE;
         }

    }
    function no_white_spaces($str)
    {
       $y = preg_match('/[\s]/i', $str);
       if($y)
       {
          $this->set_message('no_white_spaces', "%s :  contains whitespaces");
          return  FALSE;
       }
       return TRUE;
     
    }
    function alpha_dash_comma($str)
    {

        $result =  (bool) preg_match('/^[\/\+\=\s-_a-z0-9,\.\@\:]+$/i', $str);
       
        if($result === FALSE)
        {
            $this->set_message('alpha_dash_comma', "%s :  contains incorrect characters");
        }
        return $result;
    }

    function valid_contact($s)
    {
        log_message('debug','HHH : func'.serialize($s));
        $this->set_message('valid_contact', "%s :  contains incorrect characters");
        return false;
    }

    
    /**
     * Validates a date (yyyy-mm-dd)
     * 
     * @param type $date
     * @return boolean
     */
    public function valid_date($date) {
        if (!empty($date))
        {
            if(preg_match("/^(?P<year>[0-9]{4})[-](?P<month>[0-9]{2})[-](?P<day>[0-9]{2})$/", $date, $matches))
            {
                if (checkdate($matches['month'], $matches['day'], $matches['year']))    // Date really exists
                {
                    return TRUE;
                }
            }
        }
        $this->set_message('valid_date', "The %s : \"$date\" doesn't exist or invalid format. Valid format: yyyy-mm-dd.");
        return FALSE;
    }
  /**
     * Validates a date (yyyy-mm-dd) and check if not future
     * 
     * @param type $date
     * @return boolean
     */
    public function valid_date_past($date) {
        if (!empty($date))
        {
            if(preg_match("/^(?P<year>[0-9]{4})[-](?P<month>[0-9]{2})[-](?P<day>[0-9]{2})$/", $date, $matches))
            {
                if (checkdate($matches['month'], $matches['day'], $matches['year']))    // Date really exists
                {
                    $d1 = new DateTime($date);
                    $d2 = new DateTime("now");
                    if($d1 > $d2)
                    {
                        $this->set_message('valid_date_past', "The %s : \"$date\" is set in the future.");
                        return FALSE;
                        
                    }
                    else
                    {
                        return TRUE;
                    }
                }
            }
        }
        $this->set_message('valid_date_past', "The %s : \"$date\" doesn't exist or invalid format. Valid format: yyyy-mm-dd.");
        return FALSE;
   
    }
    /**
     *
     * @param type $homeorg
     * @return type boolean
     * 
     */
    function homeorg_unique($homeorg)
    {
        $ent = $this->em->getRepository("models\Provider")->findOneBy(array('name' => $homeorg));
        if (!empty($ent))
        {
            $this->set_message('homeorg_unique', "The %s : \"$homeorg\" does already exist in the system.");
            return FALSE;
        }
        else
        {
            return TRUE;
        }
    }

    function federation_unique($arg, $argtype)
    {
        if($argtype === 'name')
        {
           $attr = 'name';
        }
        elseif($argtype === 'uri')
        {
           $attr = 'urn';
        }
        else
        {
            \log_message('error',__METHOD__.' missing argtype');
            $this->set_message('federation_unique','error ocured during validation');
            return false;
        }
        $fed = $this->em->getRepository("models\Federation")->findOneBy(array(''.$attr.'' => $arg));
        if(empty($fed))
        {
           return true;
        }
        else
        {
           $this->set_message('federation_unique','The %s: '.htmlentities($arg).' already exists');
           return false;
        }

    }
    function fedcategory_unique($name,$id=null)
    {
       $ent = $this->em->getRepository("models\FederationCategory")->findOneBy(array('shortname' => $name));
       if(!empty($ent))
       {
           if(!is_null($id) && ((int) $id == $ent->getId()))
           {
               return true;
           }
           else
           {
                $this->set_message('fedcategory_unique','The %s : '.htmlentities($name).' already exists');
                return false;
           }
       }
       return true;

    }
    function cocurl_unique($url)
    {
        $e = $this->em->getRepository("models\Coc")->findOneBy(array('url' => $url));
        if(!empty($e))
        {
            $this->set_message('cocurl_unique', "The %s : \"$url\" does already exist in the system.");
            return FALSE;
        }
        else
        {
           return TRUE;
        }
    }
    function valid_contact_type($str)
    {
       $allowed = array('administrative','technical','support','billing','other');
       if(empty($str) or !in_array($str,$allowed))
       {
           $this->set_message('valid_contact_type','Invalid contact type');
           return FALSE;
       } 
       else
       {
           return TRUE;
       }
    }
    function cocurl_unique_update($url,$id)
    {
        $e = $this->em->getRepository("models\Coc")->findOneBy(array('url' => $url));
        if(!empty($e))
        {
            if($id == $e->getId())
            {
                return TRUE;
            }
            else
            {
               $this->set_message('cocurl_unique_update', "The %s : \"$url\" does already exist in the system.");
               return FALSE;
            }
        }
        else
        {
           return TRUE;
        }
    }
    function cocname_unique($name)
    {
        $e = $this->em->getRepository("models\Coc")->findOneBy(array('name' => $name));
        if(!empty($e))
        {
            $this->set_message('cocname_unique', "The %s : \"$name\" does already exist in the system.");
            return FALSE;
        }
        else
        {
           return TRUE;
        }
    }
    function cocname_unique_update($name,$id)
    {
        $e = $this->em->getRepository("models\Coc")->findOneBy(array('name' => $name));
        if(!empty($e))
        {
            if($id == $e->getId())
            {
               return TRUE;
            }
            else
            {
                $this->set_message('cocname_unique', "The %s : \"$name\" does already exist in the system.");
                return FALSE;
            }
        }
        else
        {
           return TRUE;
        }
    }
  
    function entityid_unique_update($entityid,$id)
    {
         log_message('debug', 'HHHH entity'.$entityid.' :: '.$id);
        
         $ent = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $entityid));
         if(!empty($ent))
         {
             if($id == $ent->getId())
             {
                return TRUE;
             }
             else
             {
                $this->set_message('entityid_unique_update', "The %s \"$entityid\" does belong to other provider");
                return FALSE;
             }
         }
         else
         {
             return TRUE;
         }
    }

    function ssohandler_unique($handler)
    {
        $ent = $this->em->getRepository("models\ServiceLocation")->findOneBy(array('url' => $handler));
        if (!empty($ent))
        {
            $this->set_message('ssohandler_unique', "The %s : \"$handler\" does already exist in the system.");
            return FALSE;
        }
        else
        {
            return TRUE;
        }
    }

    function entity_unique($entity)
    {
        $ent = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $entity));
        if (!empty($ent))
        {
            $this->set_message('entity_unique', "The %s : \"$entity\" does already exist in the system.");
            return FALSE;
        }
        else
        {
            return TRUE;
        }
    }

    function user_mail_unique($email)
    {
        $u = $this->em->getRepository("models\User")->findOneBy(array('email' => $email));
        if (!empty($u))
        {
            $this->set_message('user_mail_unique', "The %s : \"$email\" does already exist in the system.");
            return FALSE;
        }
        else
        {
            return TRUE;
        }
    }

    function user_username_unique($username)
    {
        $u = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        if (!empty($u))
        {
            $this->set_message('user_username_unique', "The %s : \"$username\" does already exist in the system.");
            return FALSE;
        }
        else
        {
            return TRUE;
        }
    }
    function valid_requirement_attr($req)
    {
        if($req == 'required' or $req == 'desired')
        {
            return TRUE;
        }
        else
        {
            $this->set_message('valid_requirement_attr', "Invalid value injected in requirement");
            return FALSE;
        }
    }

    function user_username_exists($username)
    {
        $u = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        if (empty($u))
        {
            $this->set_message('user_username_exists', "The %s : \"$username\" does not exist in the system.");
            return FALSE;
        }
        else
        {
            return TRUE;
        }
    }

    function verify_cert($cert)
    {
        $i = explode("\n", $cert);
        $c = count($i);
        if ($c < 2)
        {
            $pem = chunk_split($cert, 64, PHP_EOL);
            $cert = $pem;
        }
        $this->CI->load->helper('cert');
        $ncert = getPEM($cert);
        $res = openssl_x509_parse($ncert);
        if (is_array($res))
        {
           $minkeysize = $this->CI->config->item('entkeysizemin');
           if(!empty($minkeysize))
           {
              $minkeysize = (int) $minkeysize;
           }
           else
           {
              $minkeysize = 2048;
           }
           $r = openssl_pkey_get_public($ncert);
           $keysize = 0;
           if(!empty($r))
           {
              $data = array();
              $data = openssl_pkey_get_details($r);
              if(isset($data['bits']))
              {
                  $keysize=  $data['bits'];
              }
              else
              {
                  $this->set_message('verify_cert', "The %s : Could not compute keysize");
                  return false;
              } 
           }
           else
           {
              $this->set_message('verify_cert', "The %s : Keysize is less than  ".$minkeysize."");
           }
           if($minkeysize > $keysize)
           {
               $this->set_message('verify_cert', "The %s : Keysize is less than ".$minkeysize);
               return false;
           }
           return TRUE;
            
        }
        else
        {
            $this->set_message('verify_cert', "The %s : is not valid x509 cert.");
            return FALSE;
        }
    }

        function valid_extendedurl($str)
        {
		if (empty($str))
		{
			return FALSE;
		}
		else
		{
                        preg_match('/^(?:([^:]*)\:)?\/\/(.+)$/', $str, $matches);
			if (empty($matches[2]))
			{
                               $this->set_message('valid_extendedurl', "incorrect URL  \"%s\" ");

				return FALSE;
			}
			elseif ( ! in_array($matches[1], array('http', 'https','ftp','ftps')) OR empty($matches[1]) )
			{
                               $this->set_message('valid_extendedurl', "incorrect protocol  \"%s\" ");
				return FALSE;
			}
                        else
                        {
                               return TRUE;
                        }


		}

        }
	function valid_url($str)
	{
                
		if (empty($str))
		{
			return FALSE;
		}
		else
		{
                        preg_match('/^(?:([^:]*)\:)?\/\/(.+)$/', $str, $matches);
			if (empty($matches[2]))
			{
                               $this->set_message('valid_url', "incorrect URL  \"%s\" ");

				return FALSE;
			}
			elseif ( ! in_array($matches[1], array('http', 'https')) OR empty($matches[1]) )
			{
                               $this->set_message('valid_url', "incorrect protocol  \"%s\" ");
				return FALSE;
			}
                        else
                        {
                               return TRUE;
                        }


		}

	}

     function valid_url_or_empty($str)
     {
		if (empty($str))
		{
			return TRUE;
		}
		else
		{
                        preg_match('/^(?:([^:]*)\:)?\/\/(.+)$/', $str, $matches);
			if (empty($matches[2]))
			{
                               $this->set_message('valid_url_or_empty', "incorrect URL  \"%s\" ");

				return FALSE;
			}
			elseif ( ! in_array($matches[1], array('http', 'https')) OR empty($matches[1]) )
			{
                               $this->set_message('valid_url_or_empty', "incorrect protocol  \"%s\" ");
				return FALSE;
			}
                        else
                        {
                               return TRUE;
                        }


		}
     }


    function acs_index_check($acs_index)
    {
        $result = true;
        log_message('debug','HHHH:'.$acs_index);
        if (!empty($acs_index) && is_array($acs_index))
        {
            $count = count($acs_index);
            foreach ($acs_index as $key => $value)
            {
                if (($key != 'n' && !isset($value)) or $value < 0)
                {
                    $this->set_message('acs_index_check', "incorrect or no value in one of  \"%s\" " . $key . " " . $value);
                    return false;
                }
            }

            $acs_index_uniq = array_unique($acs_index);
            $count2 = count($acs_index_uniq);

            if ($count != $count2)
            {
                $this->set_message('acs_index_check', "Found duplicated values in \"%s\"");
                $result = false;
            }
        }

        return $result;
    }
    function acsindex_unique($acs_index,$field)
    {
        $a = $this->_field_data[$field]['postdata'];
        $result = true;
        if (!empty($a) && is_array($a))
        {
            if(count($a) != count(array_unique($a)))
            {
                  $this->set_message('acsindex_unique', "Incorrect or no value in one of  \"%s\"" );
                  return false;
            }

        }
        return $result;
    }


    function setup_allowed()
    {
        $x = $this->em->getRepository("models\User")->findAll();
        $count_x = count($x);
        if ($count_x > 0)
        {
            $this->set_message('setup_allowed', "Database is not empty, you cannot initialize setup");
            return FALSE;
        }
        else
        {
            return true;
        }
    }
    function valid_static($usage, $t_metadata_entity)
    {
        $tmp_array=array();
        $tmp_array=explode(':::',$t_metadata_entity);
        
        $compared_entityid  = "";
        if(array_key_exists('1',$tmp_array))
        {
            $compared_entityid  = trim($tmp_array[1]);
        }
        $is_used = $usage;
        $t_metadata = $tmp_array[0];
        $metadata = trim(base64_decode($t_metadata));





        log_message('debug', '---- validation static metadata ------');
        log_message('debug', 'is_used::' . $is_used);
        log_message('debug', 'metadata::' . $metadata);
        log_message('debug', 'entityid::' . $compared_entityid);
        if (empty($metadata))
        {
            log_message('debug', 'metadata --- empty');
        }
        else
        {
            log_message('debug', 'metadata --- not empty:');
        }
        $result = false;
        if (empty($metadata) && !empty($is_used))
        {
            log_message('debug', 'valid_static: result:: invalid metadata');
            $this->set_message('valid_static', "The %s : is empty.");
            return $result;
        }
        libxml_use_internal_errors(true);
         $this->CI->load->library('metadata_validator');
         $xmls = simplexml_load_string($metadata);
         $namespases =  h_metadataNamespaces();
         if(!empty($xmls))
         {
		//$docxml = new \DomDocument();
                //$docxml->loadXML($metadata);
               	$docxml = new \DomDocument();
		$docxml->loadXML($metadata);
		$xpath = new \DomXPath($docxml);
                foreach($namespases as $k=>$v)
                {
                    $xpath->registerNamespace(''.$k.'',''.$v.'');
                }
                $y = $docxml->saveXML();
                $first_attempt = $this->CI->metadata_validator->validateWithSchema($metadata);
                if(empty($first_attempt))
                {
			$tmp_metadata = $docxml->saveXML();
                        //log_message('debug',$tmp_metadata);
                        $second_attempt = $this->CI->metadata_validator->validateWithSchema($tmp_metadata);
                        if($second_attempt === TRUE)
                        {
                            $result = TRUE;
                        }
                        else
                        {
                           $err_details = "<br />Make sure elements contains namespaces ex. md:EntityDescriptor.";
                           $err_details .='<br />Also inside EntitiyDescriptor element you must declare namespaces defitions<br/> <code>xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"  xmlns:shibmd="urn:mace:shibboleth:metadata:1.0" xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui" xmlns:mdrpi="urn:oasis:names:tc:SAML:metadata:rpi"  xmlns:ds="http://www.w3.org/2000/09/xmldsig#"</code>';
                           $this->set_message('valid_static', "The %s : is not valid metadata.".$err_details);
                           return FALSE;
                        }


                }
                else
                {
                    $result = TRUE;
                }
                if($result)
                {
                    $entities_no = $docxml->getElementsbytagname('EntitiesDescriptor');
                    $entity_no = $docxml->getElementsbytagname('EntityDescriptor');
                    if($entities_no->length > 0)
                    {
                          $this->set_message('valid_static', "The %s : is not valid metadata<br />EntitiesDescriptor element is not allowed for single entity");
                          return FALSE;

                    }
                    if($entity_no->length != 1)
                    {
                          $this->set_message('valid_static', "The %s : is not valid metadata<br />exact one element EntityDescriptor is allowed");
                          return FALSE;

                    }
                    $ent_id = $entity_no->item(0)->getAttribute('entityID');
                    log_message('debug','-----"'.$ent_id.'" ".'.$compared_entityid.'"');
                    if(!empty($compared_entityid) && ($compared_entityid != $ent_id))
                    {
                          $this->set_message('valid_static', "The %s : is not valid metadata<br />entitID from static must match entityID in form");
                          return FALSE;
                    }
                    log_message('debug','PPPPPPPPPPPP'.$entity_no->item(0)->getAttribute('entityID'));
                     
               
                }
         }
      //  $this->CI->load->library('metadata_validator');
      //  $result = $this->CI->metadata_validator->validateWithSchema($metadata);

        if ($result === FALSE)
        {
            if (!empty($is_used))
            {
                log_message('debug', 'valid_static: result:: invalid metadata');
                           $err_details = "<br />Make sure elements contains namespaces ex. md:EntityDescriptor.";
                           $err_details .='<br />Also inside EntitiyDescriptor element you must declare namespaces defitions<br/> <code>xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"  xmlns:shibmd="urn:mace:shibboleth:metadata:1.0" xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui" xmlns:ds="http://www.w3.org/2000/09/xmldsig#"</code>';
                           $this->set_message('valid_static', "The %s : is not valid metadata.".$err_details);
            }
            else
            {
                log_message('debug', 'valid_static: result:: invalid metadata, but ignored');
                $result = TRUE;
            }
        }
        return $result;
    }
    function valid_scopes($str)
    {
                $result = TRUE;
                if(!empty($str))
                {
                    $s = explode(',',$str);
                    foreach($s as $v)
                    {
                          if(!(preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $v) && preg_match("/^.{1,253}$/", $v) && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $v)))
                          {
                              $this->set_message('valid_scopes', "%s : invalid characters");
                              return FALSE;   
                          }
                    }
                }
                return $result;
    }


    function matches_inarray($str,$serialized_array)
    {
       $array = unserialize($serialized_array);
       $result = TRUE;
       if(!in_array($str,$array))
       {
           $this->set_message('matches_inarray', "%s: doesnt match allowed value");
           $result = FALSE;
       }
       return $result;
    }

    function valid_static_old($is_used, $metadata)
    {

        $metadata = trim(base64_decode($metadata));


        log_message('debug', '---- validation static metadata ------');
        log_message('debug', 'is_used::' . $is_used);
        log_message('debug', 'metadata::' . $metadata);
        if (empty($metadata))
        {
            log_message('debug', 'metadata --- empty');
        }
        else
        {
            log_message('debug', 'metadata --- not empty:');
        }
        $result = false;
        if (empty($metadata) && !empty($is_used))
        {
            log_message('debug', 'valid_static: result:: invalid metadata');
            $this->set_message('valid_static', "The %s : is empty.");
            return $result;
        }
        $this->CI->load->library('metadata_validator');
        $result = $this->CI->metadata_validator->validateWithSchema($metadata);

        if ($result === FALSE)
        {
            if (!empty($is_used))
            {
                log_message('debug', 'valid_static: result:: invalid metadata');
                $this->set_message('valid_static_old', "The %s : is not valid metadata.");
            }
            else
            {
                log_message('debug', 'valid_static_old: result:: invalid metadata, but ignored');
                $result = TRUE;
            }
        }
        return $result;
    }

}
