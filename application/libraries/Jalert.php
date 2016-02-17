<?php
if (!defined('BASEPATH')) {
    exit('Ni direct script access allowed');
}

/**
 * @package   JAGGER
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2015 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Jalert
{
    /**
     * @var $em Doctrine\ORM\EntityManager
     */
    protected $em;

    protected $CI;


    public function __construct() {

        $this->CI = &get_instance();
        $this->em = $this->CI->doctrine->em;

    }

    public function genCertsAlerts(models\Provider $provider) {
        $result = array();
        $certificates = $provider->getCertificates();
        $this->CI->load->helper('cert');
        $minkeysize = $this->CI->config->item('entkeysizemin');
        foreach ($certificates as $certificate) {
            $cert = $certificate->getCertData();
            $i = explode("\n", $cert);
            $c = count($i);
            if ($c < 2) {
                $pem = chunk_split($cert, 64, PHP_EOL);
                $cert = $pem;
            }

            $ncert = getPEM($cert);
            $res = @openssl_x509_parse($ncert);
            if (is_array($res)) {
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
                        $result[] = array('msg' => 'Could not compute keysize', 'level' => 'warning');
                        continue;
                    }
                }

                if ($minkeysize > $keysize) {

                    $result[] = array('msg' => 'The keysize of one of the certificates is less than ' . $minkeysize, 'level' => 'warning');
                    continue;
                }

                $dateTimeNow = new DateTime('now');
                $nowInTimeStamp = $dateTimeNow->format('U');

                if (isset($res['validTo_time_t'])) {
                    $validto = new DateTime();
                    $validto->setTimestamp($res['validTo_time_t']);
                    $cfingerprint = generateFingerprint($ncert, 'sha1');
                    $diffTimeStamp = $res['validTo_time_t'] - $nowInTimeStamp;

                    if ($diffTimeStamp <= 0) {
                        $result[] = array('msg' => 'The certificate (sha1: ' . $cfingerprint . ') expired on: ' . $validto->format('Y-m-d H:i:s'), 'level' => 'alert');
                    } elseif ($diffTimeStamp < 2628000) { // will expire within month
                        $result[] = array('msg' => 'The crtificate (sha1: ' . $cfingerprint . ') will expire on: ' . $validto->format('Y-m-d H:i:s'), 'level' => 'warning');
                    }
                }
            } else {
                $result[] = array('msg' => 'One of the certificates could not be validated', 'level' => 'warning');
                continue;
            }
        }

        return $result;

    }

    public function genProviderAlertsDetails(models\Provider $provider, $msgprefix = null) {

        $result = array();


        $contacts = $provider->getContacts();
        if (count($contacts) == 0) {
            $result[] = array('msg' => 'No contacts are defined', 'level' => 'warning');
        }
        $certResult = $this->genCertsAlerts($provider);
        $result = array_merge($result, $certResult);

        /**
         * @var models\ServiceLocation[] $serviceLocation
         */
        $serviceLocation = $provider->getServiceLocations();
        $serviceUrls = array();
        foreach ($serviceLocation as $aaa) {
            $serviceUrls[] = $aaa->getUrl();
        }
        $serviceUrls = array_unique($serviceUrls);

        $srvsTcpChecked = array();
        foreach ($serviceUrls as $surl) {
            $parsedUrl = parse_url($surl);
            $urlPort = null;
            $isHostOK = true;
            $hostsByIP = array();
            if (array_key_exists('port', $parsedUrl) && !empty($parsedUrl['port'])) {
                $urlPort = $parsedUrl['port'];
            } elseif (array_key_exists('scheme', $parsedUrl)) {
                if ($parsedUrl['scheme'] === 'http') {
                    $urlPort = 80;
                } elseif ($parsedUrl['scheme'] === 'https') {
                    $urlPort = 443;
                } else {
                    $result[] = array('msg' => 'Incorrect protocol in service url :' . html_escape($surl), 'level' => 'error');
                }
            }
            if (array_key_exists('host', $parsedUrl)) {
                $srvHost = $parsedUrl['host'];
                if (!empty($srvHost) && filter_var($srvHost, FILTER_VALIDATE_IP)) {
                    $result[] = array('msg' => 'Service URL: ' . html_escape($surl) . ' -  contains IP address', 'level' => 'warning');
                    $isHostOK = false;
                } else {
                    $resolved = dns_get_record($srvHost, DNS_A + DNS_AAAA);
                    if (!empty($resolved)) {
                        foreach ($resolved as $r) {
                            if (is_array($r) && array_key_exists('ip', $r)) {

                                if (!(filter_var($r['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) && filter_var($r['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE))) {
                                    $result[] = array('msg' => 'Service URL: ' . html_escape($surl) . ' - Resolving host  result IP: ' . $r['ip'] . ' which is in private or reserved pool', 'level' => 'warning');
                                    $isHostOK = false;
                                } else {
                                    $hostsByIP['ipv4'][] = $r['ip'];
                                }
                            }
                            if (is_array($r) && array_key_exists('ipv6', $r)) {
                                if (!filter_var($r['ipv6'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
                                    $result[] = array('msg' => 'Service URL: ' . html_escape($surl) . ' - Resolving host  results : ' . $r['ipv6'] . ' which is in private or reserved pool', 'level' => 'warning');
                                    $isHostOK = false;
                                } else {
                                    $hostsByIP['ipv6'][] = $r['ipv6'];
                                }
                            }
                        }
                    } else {
                        $result[] = array('msg' => 'Service URL: ' . html_escape($surl) . ' - Could not resolve a domain from service URL: ', 'level' => 'warning');
                        $isHostOK = false;
                    }
                }
            } else {
                $isHostOK = false;
            }
            if ($isHostOK === true && !empty($urlPort)) {
                if (array_key_exists('ipv4', $hostsByIP)) {
                    foreach ($hostsByIP['ipv4'] as $ip) {

                        if (!in_array('' . $ip . '_' . $urlPort . '', $srvsTcpChecked, true)) {
                            $fp = @fsockopen($ip, $urlPort, $errno, $errstr, 2);
                            if (!$fp) {
                                $result[] = array('msg' => 'Service URL: ' . html_escape($surl) . ' : ' . $ip . ' : ' . $errstr . ' (' . $errno . ')', 'level' => 'alert');
                            }
                            $srvsTcpChecked[] = '' . $ip . '_' . $urlPort;
                        }
                    }
                }
                if (array_key_exists('ipv6', $hostsByIP)) {


                    foreach ($hostsByIP['ipv6'] as $ip) {
                        if (!in_array('' . $ip . '_' . $urlPort, $srvsTcpChecked, true)) {
                            $fp = @fsockopen('tcp://[' . $ip . ']', $urlPort, $errno, $errstr, 2);
                            if (!$fp) {
                                $result[] = array('msg' => 'Service URL: ' . html_escape($surl) . ' : ' . $ip . ' : ' . $errstr . ' (' . $errno . ')', 'level' => 'alert');
                            }
                            $srvsTcpChecked[] = '' . $ip . '_' . $urlPort;
                        }
                    }
                }
            }
        }


        if (count($result) == 0) {
            $result = array();
        }

        if (!empty($msgprefix)) {
            foreach ($result as $k => $v) {
                $result['' . $k . '']['msg'] = '(' . $msgprefix . ') ' . $v['msg'];
            }
        }

        return $result;
    }


}
