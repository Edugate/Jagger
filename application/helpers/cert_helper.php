<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2014, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * RR3 Helpers
 *
 * @package     RR3
 * @subpackage  Helpers
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */


function validateX509($cert, $args = NULL)
{
    if (empty($cert))
    {
        return FALSE;
    } else
	{
		$cert = getPEM($cert);
        $cert_result = openssl_x509_parse($cert);
        if (empty($cert_result) OR !is_array($cert_result))
        {
            return FALSE;
        }
        if (!empty($args) && is_array($args) && count($args) > 0)
        {
            if (array_key_exists('validity', $args) && ($args['validity'] == TRUE))
            {
                /**
                 * TODO
                 */
            }
        } else
        {
            return TRUE;
        }
    }
}

function getKeysize($cert)
{
   $r = openssl_pkey_get_public($cert);
   $result = null;
   if(!empty($r))
   {
       $data = array();
       $data = openssl_pkey_get_details($r);
       if(isset($data['bits']))
       {
          return $data['bits'];
       }
   }
   return $result;
}

function generateFingerprint($certdata,$alg)
{
        $fingerprint = null;
        if (!empty($certdata))
        {
            $cert = getPEM($certdata);
            $resource = openssl_x509_read($cert);
            $output = null;
            $result = openssl_x509_export($resource, $output);
            if ($result !== false)
            {
                $output = str_replace('-----BEGIN CERTIFICATE-----', '', $output);
                $output = str_replace('-----END CERTIFICATE-----', '', $output);
                $output = base64_decode($output);
                $fingerprint = $alg($output);
            }
        }
        return $fingerprint;
}

function reformatPEM($value)
{
    if(!empty($value))
    {
       $cleaned_value = $value;
       $cleaned_value = str_replace('-----BEGIN CERTIFICATE-----', '', $cleaned_value);
       $cleaned_value = str_replace('-----END CERTIFICATE-----', '', $cleaned_value);
       $cleaned_value = preg_replace("/\r\n/","", $cleaned_value);
       $cleaned_value = preg_replace("/\n+/","", $cleaned_value);
       $cleaned_value = preg_replace('/\s\s+/', "", $cleaned_value);
       $cleaned_value = preg_replace('/\s*/', "", $cleaned_value);
       $cleaned_value= trim($cleaned_value);
       $pem = chunk_split($cleaned_value, 64, PHP_EOL);
       return $pem;
    }
    else
    {
       return $value;
    }
}

// Get PEM formated certificate from quickform input
// if raw is true, then ommit the begin/end certificate delimiter
function getPEM($value=null, $raw = false)
{
    if(empty($value))
    {
        return null;
    }
    $pattern = array(
       '0'=>'/(.*)-----BEGIN CERTIFICATE-----/s',
       '1'=>'/-----END CERTIFICATE-----(.*)/s'
    );
    $cleaner = array(
       '0'=>'',
       '1'=>''
    );
    $replacement = array(
      '0'=>"-----BEGIN CERTIFICATE-----\n",
      '1'=>"\n-----END CERTIFICATE-----"
     );
    $cleaned_value = preg_replace($pattern, $cleaner, $value);
    $cleaned_value = trim($cleaned_value);

    $cleaned_value = preg_replace('#(\\\r)#', '', $cleaned_value);
    $cleaned_value = preg_replace('#(\\\n)#', "\n", $cleaned_value);

    $cleaned_value = trim($cleaned_value);

    // Add or remove BEGIN/END lines
    if ($raw===false)
    {
        $cleaned_value = $replacement['0'].$cleaned_value.$replacement['1'];
    }
    return $cleaned_value;
}

function PEMtoHTML($value)
{
    $cleaned_value = nl2br($value);
    return $cleaned_value;
}
