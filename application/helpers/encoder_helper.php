<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ResourceRegistry3
 *
 * @package     RR3
 * @author      Middleware Team HEAnet
 * @copyright   Copyright (c) 2013, HEAnet Limited (http://www.heanet.ie)
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
function base64url_encode($plainText) {
    $base64 = base64_encode($plainText);
    $base64url = strtr($base64, '+/=', '-_~');

    return $base64url;
}

function base64url_decode($encoded) {
    $base64 = strtr($encoded, '-_~', '+/=');
    $plainText = base64_decode($base64);

    return $plainText;
}


function urlsafeB64Decode($input) {
    $remainder = strlen($input) % 4;
    if ($remainder) {
        $padlen = 4 - $remainder;
        $input .= str_repeat('=', $padlen);
    }

    return base64_decode(strtr($input, '-_', '+/'));
}

function urlsafeB64Encode($input) {
    return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
}

function getCachePrefix() {
    return md5(base_url()) . '_rr3_';

}


function jXMLFilter($str) {
    $remove_php_regex = '/(<\?{1}[pP\s]{1}.+\?>)/Us';
    $remove_php_replacement = '';
    $result = preg_replace($remove_php_regex, $remove_php_replacement, $str);

    return $result;

}

function jSAMLDecoder($encoded) {
    $str1 = rawurldecode(stripslashes($encoded));
    $str2 = base64_decode($str1);
    $str3 = @gzinflate($str2);
    if ($str3 !== false) {
        return $str3;
    }

    return $str2;
}

function arrayWithKeysToHtml($a) {
    $str = '';
    foreach ($a as $key => $value) {
        $str .= html_escape($key) . ': ' . html_escape($value) . '<br />';
    }

    return $str;

}

function doubleDashXmlComment($str){
    return str_replace("--",'&#x002d;&#x002d',$str);

}
