<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2016, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class MY_form_validation extends CI_form_validation
{

    protected $em;

    public function __construct() {
        parent::__construct();
        $this->em = $this->CI->doctrine->em;
        $this->CI->load->helper('metadata_elements');
    }


    public function xss_clean($str) {
        return $this->CI->security->xss_clean($str);
    }


    public function str_matches_array($str, $arr) {
        $result = false;
        $arr = unserialize($arr);
        if (empty($str)) {
            if (count($arr) == 0) {
                $result = true;
            }
        } else {
            $ar1 = explode(",", $str);
            if (count(array_diff($ar1, $arr)) == 0 && count(array_diff($arr, $ar1)) == 0) {
                $result = true;
            }

        }
        if (!$result) {
            $this->set_message('str_matches_array', 'The %s  must not been changed to ' . htmlentities($str));
        }

        return $result;
    }

    public function mustmatch_value($str1, $str2) {
        if (strcmp($str1, $str2) === 0) {
            return true;
        }
        $this->set_message('mustmatch_value', 'The %s: ' . html_escape($str2) . ' must not been changed to ' . html_escape($str1));
        return false;
    }

    public function no_white_spaces($str) {
        $y = preg_match('/[\s]/i', $str);
        if ($y) {
            $this->set_message('no_white_spaces', "%s :  contains whitespaces");
            return false;
        }
        return true;
    }

    public function alpha_dash_comma($str) {

        $result = (bool)preg_match('/^[\/\+\=\s-_a-z0-9,\.\@\:]+$/i', $str);

        if ($result === false) {
            $this->set_message('alpha_dash_comma', "%s :  contains incorrect characters");
        }

        return $result;
    }

    public function valid_domain($domain) {
        $result = preg_match('/^ (?: [a-z0-9] (?:[a-z0-9\-]* [a-z0-9])? \. )* [a-z0-9] (?:[a-z0-9\-]* [a-z0-9])?  \. [a-z]{2,6} $ /ix', $domain);
        if ($result) {
            return true;
        }
        $this->set_message('valid_domain', "%s :  invalid domain: " . html_escape($domain));

        return false;

    }

    public function valid_ip_with_prefix($str) {
        $ip = substr($str, 0, strpos($str . '/', '/'));
        $range = substr($str, strpos($str . '/', '/') + 1);
        if (empty($range) || !ctype_digit($range)) {
            $this->set_message('valid_ip_with_prefix', '%s: ' . htmlentities($str) . ' missing or invalid  prefix');

            return false;
        }

        $isIPV4 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE);
        $isIPV6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE);
        if ($isIPV4 !== false) {

            if ($range <= 32 && $range > 0) {
                return true;
            }
            $this->set_message('valid_ip_with_prefix', '%s: ' . htmlentities($str) . ': incorrect  network prefix for IPV4');

            return false;

        }
        if ($isIPV6 !== false) {
            if ($range <= 64 && $range >= 24) {
                return true;
            }
            $this->set_message('valid_ip_with_prefix', '%s: ' . htmlentities($str) . ': incorrect  network prefix IPV6');

            return false;
        }
        $this->set_message('valid_ip_with_prefix', '%s: ' . htmlentities($str) . ': invalid IP or IP is from private network range');

        return false;
    }

    /**
     * validates str if is urn or url - for example: validation of entityid
     *
     */
    public function valid_urnorurl($str) {
        $urnRegex = '/^urn:[a-z0-9][a-z0-9-]{1,31}:([a-z0-9()+,-.:=@;$_!*\']|%(0[1-9a-f]|[1-9a-f][0-9a-f]))+$/i';
        $isUrnValid = (bool)preg_match($urnRegex, $str);
        if ($isUrnValid) {
            return true;
        }
        $isValidUrl = parent::valid_url($str);
        if ($isValidUrl) {
            return true;
        }
        $this->set_message('valid_urnorurl', "%s : contains invalid URI");

        return false;
    }

    public function validimageorurl($str) {
        $isValidUrl = parent::valid_url($str);
        if ($isValidUrl) {
            return true;
        }
        $str1pos = strpos($str, 'data:');
        $str2pos = strpos($str, 'base64,');
        if ($str1pos === 0 && (30 > $str2pos) && ($str2pos > 0)) {
            $cutpos = $str2pos + 7;
            $substr = substr($str, $cutpos);
            $img = base64_decode($substr);
            if (function_exists('imagecreatefromstring')) {
                $img2 = imagecreatefromstring($img);
                if (!$img2) {
                    $this->set_message('validimageorurl', "%s : contains invalid imagedata");

                    return false;
                }
            } else {
                $this->set_message('validimageorurl', "%s : cannot validate imagedata");

                return false;
            }

            return true;

        }
        $this->set_message('validimageorurl', "%s : contains invalid URL/imagedata");

        return false;
    }

    /**
     * Validates a date (yyyy-mm-dd)
     *
     * @param type $date
     * @return boolean
     */
    public function valid_date($date) {
        if (!empty($date)) {
            if (preg_match("/^(?P<year>[0-9]{4})[-](?P<month>[0-9]{2})[-](?P<day>[0-9]{2})$/", $date, $matches)) {
                if (checkdate($matches['month'], $matches['day'], $matches['year']))    // Date really exists
                {
                    return true;
                }
            }
        }
        $this->set_message('valid_date', "The %s : \"$date\" doesn't exist or invalid format. Valid format: yyyy-mm-dd.");

        return false;
    }

    public function valid_time_hhmm($time) {
        $e = explode(":", $time);
        if (count($e) === 2 && is_numeric($e['0']) && is_numeric($e['1']) && ($e['0'] < 24 && $e['0'] >= 0) && ($e['1'] >= 0 && $e['1'] < 60)) {
            return true;
        }
        $this->set_message('valid_time_hhmm', "The %s : invalid format. Valid format: HH:mm.");

        return false;
    }

    /**
     * Validates a date (yyyy-mm-dd) and check if not future
     *
     * @param type $date
     * @return boolean
     */
    public function valid_date_past($date) {
        if (!empty($date)) {
            if (preg_match("/^(?P<year>[0-9]{4})[-](?P<month>[0-9]{2})[-](?P<day>[0-9]{2})$/", $date, $matches)) {
                if (checkdate($matches['month'], $matches['day'], $matches['year']))    // Date really exists
                {
                    $d1 = new DateTime($date);
                    $d2 = new DateTime("now");
                    if ($d1 > $d2) {
                        $this->set_message('valid_date_past', "The %s : \"$date\" is set in the future.");

                        return false;

                    } else {
                        return true;
                    }
                }
            }
        }
        $this->set_message('valid_date_past', "The %s : \"$date\" doesn't exist or invalid format. Valid format: yyyy-mm-dd.");

        return false;

    }

    /**
     *
     * @param type $homeorg
     * @return type boolean
     *
     */
    public function homeorg_unique($homeorg) {
        /**
         * @var models\Provider $ent
         */
        $ent = $this->em->getRepository("models\Provider")->findOneBy(array('name' => $homeorg));
        if ($ent !== null) {
            $this->set_message('homeorg_unique', "The %s : \"$homeorg\" does already exist in the system.");
            return false;
        }
        return true;

    }


    public function federation_updateunique($value, $params) {
        $p = unserialize($params);
        if (!isset($p['fedid'])) {
            $this->set_message('federation_updateunique', 'The %s: ' . htmlentities($value) . ' missing federation id');

            return false;
        }
        $pid = $p['fedid'];
        $attr = $p['attr'];
        /**
         * @var models\Federation $fed
         */
        $fed = $this->em->getRepository("models\Federation")->findOneBy(array($attr => $value));
        if ($fed === null) {
            return true;
        }
        $fedid = $fed->getId();
        if ((int)$pid === (int)$fedid) {
            return true;
        }
        $this->set_message('federation_updateunique', 'The %s: ' . htmlentities($value) . ' already exists');

        return false;


    }

    public function federation_unique($arg, $argtype) {
        if ($argtype === 'name') {
            $attr = 'name';
        } elseif ($argtype === 'uri') {
            $attr = 'urn';
        } elseif ($argtype === 'sysname') {
            $attr = 'sysname';
        } else {
            \log_message('error', __METHOD__ . ' missing argtype');
            $this->set_message('federation_unique', 'error ocured during validation');
            return false;
        }
        $fed = $this->em->getRepository("models\Federation")->findOneBy(array('' . $attr . '' => $arg));
        if ($fed === null) {
            return true;
        }
        $this->set_message('federation_unique', 'The %s: ' . htmlentities($arg) . ' already exists');

        return false;


    }

    public function mailtemplate_unique($group, $field) {
        if (isset($this->_field_data[$field], $this->_field_data[$field]['postdata'])) {
            $jlang = $this->_field_data[$field]['postdata'];
        } else {
            return true;

        }

        /**
         * @var models\MailLocalization $l
         */
        $l = $this->em->getRepository("models\MailLocalization")->findOneBy(array('mgroup' => $group, 'lang' => $jlang));
        if ($l !== null) {
            $this->set_message('mailtemplate_unique', 'The template already exists for language: ' . html_escape($jlang));

            return false;
        }

        return true;
    }

    public function mailtemplate_isdefault($isdefault, $field) {
        if (isset($this->_field_data[$field], $this->_field_data[$field]['postdata'])) {
            $group = $this->_field_data[$field]['postdata'];
        } else {
            return true;

        }
        if (!empty($isdefault) || strcmp($isdefault, 'yes') != 0) {
            return true;
        }

        $l = $this->em->getRepository("models\MailLocalization")->findOneBy(array('mgroup' => $group, 'isdefault' => true));
        if ($l !== null) {
            $this->set_message('mailtemplate_isdefault', 'Templeate with specidi group already has default ');

            return false;
        }

        return true;
    }

    public function attribute_unique($value, $name) {
        $attr = $this->em->getRepository("models\Attribute")->findOneBy(array('' . $name . '' => $value));
        if (empty($attr)) {
            return true;
        }
        $this->set_message('attribute_unique', '%s: already exists in the system');

        return false;

    }

    public function fedcategory_unique($name, $id = null) {
        $ent = $this->em->getRepository("models\FederationCategory")->findOneBy(array('shortname' => $name));
        if ($ent !== null) {
            if (!is_null($id) && ((int)$id == $ent->getId())) {
                return true;
            } else {
                $this->set_message('fedcategory_unique', 'The %s : ' . htmlentities($name) . ' already exists');

                return false;
            }
        }

        return true;

    }

    public function cocurl_unique($url) {
        $e = $this->em->getRepository("models\Coc")->findOneBy(array('url' => $url));
        if ($e !== null) {
            $this->set_message('cocurl_unique', "The %s : \"$url\" does already exist in the system.");

            return false;
        } else {
            return true;
        }
    }

    public function valid_contact_type($str) {
        $allowed = array('administrative', 'technical', 'support', 'billing', 'other');
        if (empty($str) || !in_array($str, $allowed)) {
            $this->set_message('valid_contact_type', 'Invalid contact type');

            return false;
        } else {
            return true;
        }
    }


    public function ecUrlInsert($url, $attrname) {
        $e = $this->em->getRepository("models\Coc")->findOneBy(array('url' => $url, 'subtype' => $attrname, 'type' => 'entcat'));
        if ($e !== null) {
            $this->set_message('ecUrlInsert', "The %s : (" . $attrname . " : " . $url . ") does already exist in the system.");

            return false;

        }

        return true;
    }

    public function ecUrlUpdate($url, $params) {
        $p = unserialize($params);
        $e = $this->em->getRepository("models\Coc")->findBy(array('url' => $url, 'subtype' => $p['subtype'], 'type' => 'entcat'));
        $id = $p['id'];
        $found = false;
        foreach ($e as $v) {
            $vId = $v->getId();
            if ($id != $vId) {
                $found = true;
                break;
            }
        }
        if ($found) {
            $this->set_message('ecUrlUpdate', "The %s :\"$url\" does already exist for \"$attrname\"");

            return false;


        }

        return true;

    }

    public function cocurl_unique_update($url, $id) {
        $e = $this->em->getRepository("models\Coc")->findOneBy(array('url' => $url));
        if ($e !== null) {
            if ($id == $e->getId()) {
                return true;
            } else {
                $this->set_message('cocurl_unique_update', "The %s : \"$url\" does already exist in the system.");

                return false;
            }
        }
        return true;

    }

    public function cocname_unique($name) {
        $e = $this->em->getRepository("models\Coc")->findOneBy(array('name' => $name));
        if ($e !== null) {
            $this->set_message('cocname_unique', "The %s : \"$name\" does already exist in the system.");

            return false;
        }
        return true;

    }

    public function cocname_unique_update($name, $id) {
        $e = $this->em->getRepository("models\Coc")->findOneBy(array('name' => $name));
        if ($e !== null) {
            if ($id == $e->getId()) {
                return true;
            }
            $this->set_message('cocname_unique_update', "The %s : \"$name\" does already exist in the system.");

            return false;

        }

        return true;

    }

    public function entityid_unique_update($entityid, $id) {
        log_message('debug', 'HHHH entity' . $entityid . ' :: ' . $id);

        $ent = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $entityid));
        if ($ent !== null) {
            if ((string)$id === (string)$ent->getId()) {
                return true;
            }
            $this->set_message('entityid_unique_update', "The %s \"$entityid\" does belong to other provider");

            return false;

        }

        return true;

    }

    public function ssohandler_unique($handler) {
        $ent = $this->em->getRepository("models\ServiceLocation")->findOneBy(array('url' => $handler));
        if (!empty($ent)) {
            $this->set_message('ssohandler_unique', "The %s : \"$handler\" does already exist in the system.");

            return false;
        }

        return true;
    }

    public function entity_unique($entity) {
        $ent = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $entity));
        if ($ent !== null) {
            $this->set_message('entity_unique', "The %s : \"$entity\" does already exist in the system.");

            return false;
        }

        return true;

    }

    public function spage_unique($pcode) {
        if (strcasecmp($pcode, 'new') == 0) {
            $this->set_message('spage_unique', "The %s : \"$pcode\" is not allowed. Please choose different code");

            return false;

        }
        $page = $this->em->getRepository("models\Staticpage")->findOneBy(array('pcode' => $pcode));
        if ($page !== null) {
            $this->set_message('spage_unique', "The %s : \"$pcode\" does already exist in the system.");

            return false;
        }

        return true;


    }

    public function user_mail_unique($email) {
        $user = $this->em->getRepository("models\User")->findOneBy(array('email' => $email));
        if ($user !== null) {
            $this->set_message('user_mail_unique', "The %s : \"$email\" does already exist in the system.");

            return false;
        }

        return true;

    }

    public function user_username_unique($username) {
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        $rolename = $this->em->getRepository("models\AclRole")->findOneBy(array('name' => $username));
        if ($user !== null || $rolename !== null) {
            $this->set_message('user_username_unique', "The %s : \" $username\" does already exist in the system or conflicts with role names.");

            return false;
        }

        return true;

    }

    public function valid_requirement_attr($req) {
        if ($req == 'required' || $req == 'desired') {
            return true;
        } else {
            $this->set_message('valid_requirement_attr', "Invalid value injected in requirement");

            return false;
        }
    }

    public function user_username_exists($username) {
        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        if ($user === null) {
            $this->set_message('user_username_exists', "The %s : \"$username\" does not exist in the system.");

            return false;
        }

        return true;

    }


    public function verify_cert_nokeysize($cert) {
        $i = explode("\n", $cert);
        $c = count($i);
        if ($c < 2) {
            $pem = chunk_split($cert, 64, PHP_EOL);
            $cert = $pem;
        }
        $this->CI->load->helper('cert');
        $ncert = getPEM($cert);
        $res = openssl_x509_parse($ncert);
        if (is_array($res)) {
            return true;
        }
        $this->set_message('verify_cert_nokeysize', "The %s : is not valid x509 cert.");

        return false;
    }

    public function verify_cert($cert) {
        $i = explode("\n", $cert);
        $c = count($i);
        if ($c < 2) {
            $pem = chunk_split($cert, 64, PHP_EOL);
            $cert = $pem;
        }
        $this->CI->load->helper('cert');
        $ncert = getPEM($cert);
        $res = openssl_x509_parse($ncert);
        if (is_array($res)) {
            $minkeysize = $this->CI->config->item('entkeysizemin');
            if (!empty($minkeysize)) {
                $minkeysize = (int)$minkeysize;
            } else {
                $minkeysize = 2048;
            }
            $r = openssl_pkey_get_public($ncert);
            $keysize = 0;
            if (!empty($r)) {
                $data = openssl_pkey_get_details($r);
                if (isset($data['bits'])) {
                    $keysize = $data['bits'];
                } else {
                    $this->set_message('verify_cert', "The %s : Could not compute keysize");

                    return false;
                }
            } else {
                $this->set_message('verify_cert', "The %s : Keysize is less than  " . $minkeysize . "");
            }
            if ($minkeysize > $keysize) {
                $this->set_message('verify_cert', "The %s : Keysize is less than " . $minkeysize);

                return false;
            }

            return true;

        }
        $this->set_message('verify_cert', "The %s : is not valid x509 cert.");

        return false;
    }

    public function valid_extendedurl($str) {
        if (empty($str)) {
            return false;
        }
        preg_match('/^(?:([^:]*)\:)?\/\/(.+)$/', $str, $matches);
        if (empty($matches[2])) {
            $this->set_message('valid_extendedurl', "incorrect URL  \"%s\" ");

            return false;
        }
        if (!in_array($matches[1], array('http', 'https', 'ftp', 'ftps')) || empty($matches[1])) {
            $this->set_message('valid_extendedurl', "incorrect protocol  \"%s\" ");

            return false;
        }
        return true;


    }

    public function valid_url($str) {
        $isValidURL = parent::valid_url($str);
        if (!$isValidURL) {
            return false;
        }
        preg_match('/^(?:([^:]*)\:)?\/\/(.+)$/', $str, $matches);
        if (!isset($matches[1])) {
            $this->set_message('valid_url', "missing protocol  \"%s\" ");
            return false;
        }
        if (!in_array($matches[1], array('http', 'https'))) {
            $this->set_message('valid_url', "incorrect protocol  \"%s\" ");
            return false;
        }
        return true;
    }

    public function valid_url_ssl($str) {
        $isValidURL = parent::valid_url($str);
        if (!$isValidURL) {
            return false;
        }
        preg_match('/^(?:([^:]*)\:)?\/\/(.+)$/', $str, $matches);
        if (!isset($matches[1])) {
            $this->set_message('valid_url', "missing protocol  \"%s\" ");
            return false;
        }
        if (!in_array($matches[1], array('https'), true)) {
            $this->set_message('valid_url', "Only https protocol is allowed  \"%s\" ");
            return false;
        }
        return true;

    }

    public function match_language($str) {
        $langs = languagesCodes();

        if (array_key_exists($str, $langs)) {
            return true;
        }
        $this->set_message('match_language', '' . lang('wronglangcode') . ': ' . htmlentities($str));

        return false;

    }

    public function valid_latlng($str) {
        $pattern = '/^-?([0-8]?[0-9]|90)\.[0-9]{1,15},-?((1?[0-7]?|[0-9]?)[0-9]|180)\.[0-9]{1,15}$/';
        $res = preg_match($pattern, $str);
        if (!$res) {
            $this->set_message('valid_latlng', "Incorrect or too long  \"%s\" ");

            return false;
        }

        return true;

    }

    public function valid_url_or_empty($str) {
        if ($str === '') {
            return true;
        }
        $isValid = $this->valid_url($str);
        return $isValid;

    }


    public function acs_index_check($acs_index) {
        if (!empty($acs_index) && is_array($acs_index)) {
            $count = count($acs_index);
            foreach ($acs_index as $key => $value) {
                if (($key != 'n' && !isset($value)) || $value < 0) {
                    $this->set_message('acs_index_check', "incorrect or no value in one of  \"%s\" " . $key . " " . $value);

                    return false;
                }
            }
            $acs_index_uniq = array_unique($acs_index);
            $count2 = count($acs_index_uniq);
            if ($count != $count2) {
                $this->set_message('acs_index_check', "Found duplicated values in \"%s\"");

                return false;
            }
        }

        return true;
    }

    public function acsindex_unique($acs_index, $field) {
        $a = $this->_field_data[$field]['postdata'];
        if (!empty($a) && is_array($a)) {
            if (count($a) != count(array_unique($a))) {
                $this->set_message('acsindex_unique', "Incorrect or no value in one of  \"%s\"");

                return false;
            }
        }

        return true;
    }


    public function setup_allowed() {
        $x = $this->em->getRepository("models\User")->findAll();
        if (count($x) > 0) {
            $this->set_message('setup_allowed', "Database is not empty, you cannot initialize setup");

            return false;
        }

        return true;

    }


    public function valid_static($usage, $t_metadata_entity) {
        $tmp_array = explode(':::', $t_metadata_entity);

        $compared_entityid = '';
        if (array_key_exists('1', $tmp_array)) {
            $compared_entityid = trim($tmp_array[1]);
        }
        $is_used = $usage;
        $t_metadata = $tmp_array[0];
        $metadata = trim(base64_decode($t_metadata));


        log_message('debug', '---- validation static metadata ------');
        log_message('debug', 'is_used::' . $is_used);
        log_message('debug', 'metadata::' . $metadata);
        log_message('debug', 'entityid::' . $compared_entityid);
        if (empty($metadata)) {
            log_message('debug', 'metadata --- empty');
        } else {
            log_message('debug', 'metadata --- not empty:');
        }
        $result = false;
        if (empty($metadata) && !empty($is_used)) {
            log_message('debug', 'valid_static: result:: invalid metadata');
            $this->set_message('valid_static', "The %s : is empty.");

            return $result;
        }
        libxml_use_internal_errors(true);
        $this->CI->load->library('metadatavalidator');

        $xmls = simplexml_load_string($metadata);
        $namespases = h_metadataNamespaces();
        if (!empty($xmls)) {
            $docxml = new \DomDocument();
            $docxml->loadXML($metadata);
            $xpath = new \DomXPath($docxml);
            foreach ($namespases as $k => $v) {
                $xpath->registerNamespace('' . $k . '', '' . $v . '');
            }
            $y = $docxml->saveXML();

            log_message('debug', $y);
            $first_attempt = $this->CI->metadatavalidator->validateWithSchema($metadata);
            if (empty($first_attempt)) {
                $tmp_metadata = $docxml->saveXML();
                $second_attempt = $this->CI->metadatavalidator->validateWithSchema($tmp_metadata);
                if ($second_attempt === true) {
                    $result = true;
                } else {
                    $err_details = "<br />Make sure elements contains namespaces ex. md:EntityDescriptor.";
                    $err_details .= '<br />Also inside EntitiyDescriptor element you must declare namespaces defitions<br/> <code>xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"  xmlns:shibmd="urn:mace:shibboleth:metadata:1.0" xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui" xmlns:mdrpi="urn:oasis:names:tc:SAML:metadata:rpi"  xmlns:ds="http://www.w3.org/2000/09/xmldsig#"</code>';
                    $this->set_message('valid_static', "The %s : is not valid metadata." . $err_details);

                    return false;
                }


            } else {
                $result = true;
            }
            if ($result) {
                $entities_no = $docxml->getElementsbytagname('EntitiesDescriptor');
                $entity_no = $docxml->getElementsbytagname('EntityDescriptor');
                if ($entities_no->length > 0) {
                    $this->set_message('valid_static', "The %s : is not valid metadata<br />EntitiesDescriptor element is not allowed for single entity");

                    return false;

                }
                if ($entity_no->length != 1) {
                    $this->set_message('valid_static', "The %s : is not valid metadata<br />exact one element EntityDescriptor is allowed");

                    return false;

                }
                $ent_id = $entity_no->item(0)->getAttribute('entityID');
                log_message('debug', '-----"' . $ent_id . '" ".' . $compared_entityid . '"');
                if (!empty($compared_entityid) && ($compared_entityid != $ent_id)) {
                    $this->set_message('valid_static', "The %s : is not valid metadata<br />entitID from static must match entityID in form");

                    return false;
                }
                log_message('debug', 'PPPPPPPPPPPP' . $entity_no->item(0)->getAttribute('entityID'));


            }
        }

        if ($result === false) {
            if (!empty($is_used)) {
                log_message('debug', 'valid_static: result:: invalid metadata');
                $err_details = "<br />Make sure elements contains namespaces ex. md:EntityDescriptor.";
                $err_details .= '<br />Also inside EntitiyDescriptor element you must declare namespaces defitions<br/> <code>xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"  xmlns:shibmd="urn:mace:shibboleth:metadata:1.0" xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui" xmlns:ds="http://www.w3.org/2000/09/xmldsig#"</code>';
                $this->set_message('valid_static', "The %s : is not valid metadata." . $err_details);
            } else {
                log_message('debug', 'valid_static: result:: invalid metadata, but ignored');
                $result = true;
            }
        }

        return $result;
    }

    public function valid_scopes($str) {
        if (!empty($str)) {
            $s = preg_split("/[\s,]+/", $str);
            foreach ($s as $v) {
                if (!(preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $v) && preg_match("/^.{1,253}$/", $v) && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $v))) {
                    $this->set_message('valid_scopes', "%s : invalid characters");

                    return false;
                }
            }
        }

        return true;
    }


    public function matches_inarray($str, $serialized_array) {
        $array = unserialize($serialized_array);
        if (!in_array($str, $array)) {
            $this->set_message('matches_inarray', "%s: doesnt match allowed value");
            return false;
        }

        return true;
    }

    public function valid_cronminute($str) {
        $result = (bool)preg_match('/^[\*,\/\-0-9]+$/', $str);
        if ($result !== true) {
            $this->set_message('valid_cronminute', "%s : is incorrect.");

            return false;
        }

        return true;
    }

    public function valid_cronhour($str) {
        $result = (bool)preg_match('/^[\*,\/\-0-9]+$/', $str);
        if ($result !== true) {
            $this->set_message('valid_cronhour', "%s : is incorrect.");
        }
        return $result;
    }

    public function valid_crondom($str) {
        $result = (bool)preg_match('/^[\*,\/\-\?LW0-9A-Za-z]+$/', $str);
        if ($result !== true) {
            $this->set_message('valid_crondom', "%s : is incorrect.");
        }
        return $result;
    }

    public function valid_crondow($str) {

        foreach (explode(',', $str) as $expr) {
            if (!preg_match('/^(\*|[0-7](L?|#[1-5]))([\/\,\-][0-7]+)*$/', $expr)) {
                $this->set_message('valid_crondow', "%s : is incorrect.");
                return false;
            }
        }
        return true;
    }

    public function valid_cronmonth($str) {
        $result = (bool)preg_match('/^[\*,\/\-0-9A-Z]+$/', $str);
        if ($result !== true) {
            $this->set_message('valid_cronmonth', "%s : is incorrect.");
        }
        return $result;
    }
}
