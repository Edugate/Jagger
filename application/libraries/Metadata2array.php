<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
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
 * Metadata2array Class
 *
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Metadata2array
{

    private $i;
    private $occurance;
    private $metaArray;
    private $coclist;
    private $regpollist;
    private $nameidsattrs = array();
    private $newNameSpaces = array();
    protected $ci;
    protected $convertNameIdToAttrs;
    protected $allowedEntcats = array();
    /**
     * @var Doctrine\ORM\EntityManager $em
     */
    protected $em;
    /**
     * @var \DOMDocument $doc
     * @var \DomXPath $xpath
     */
    protected $doc, $xpath;

    public function __construct() {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $cnfConvertNameIdsToAttrs = $this->ci->config->item('importnameidstoattrs');
        $this->convertNameIdToAttrs = true;
        if($cnfConvertNameIdsToAttrs === false){
            $this->convertNameIdToAttrs = false;
        }


        $this->i = 0;
        $this->occurance = array();
        $this->metaArray = array();
        $this->coclist = array();
        $this->regpollist = array();
        $this->allowedEntcats = attrsEntCategoryList();
        /**
         * @var $tmpnemaids models\Attribute[]
         */
        $tmpnemaids = $this->em->getRepository("models\Attribute")->findBy(array('name' => array('persistentId', 'transientId')));
        foreach ($tmpnemaids as $p) {
            $this->nameidsattrs['' . $p->getName() . ''] = $p->getOid();
        }
    }

    public function rootConvert($xml, $full = false) {
        if (!$xml instanceOf \DOMDocument) {
            $this->doc = new \DOMDocument();
            $this->xpath = new \DomXPath($this->doc);
            $this->doc->loadXML($xml);
        } else {
            $this->doc = $xml;
            $this->xpath = new \DomXPath($this->doc);
        }
        $namespaces = h_metadataNamespaces();
        foreach ($namespaces as $key => $value) {
            $this->xpath->registerNamespace($key, $value);
        }
        foreach ($this->doc->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $this->entitiesConvert($child, $full);
            }
        }

        foreach ($this->coclist as $attrname => $attrvalues) {
            $reducedList = array_unique($attrvalues);
            foreach ($reducedList as $r) {
                $existing = $this->em->getRepository("models\Coc")->findOneBy(array('url' => $r, 'type' => 'entcat', 'subtype' => $attrname));
                if (null === $existing) {
                    $nconduct = new models\Coc;
                    $nconduct->setEntityCategory($r, $r, $attrname, '' . $attrname . ': ' . $r . '', false);
                    $this->em->persist($nconduct);
                }
            }
        }
        foreach ($this->regpollist as $k => $v) {
            $reducedList = array_unique($v);
            foreach ($reducedList as $c) {
                $existing = $this->em->getRepository("models\Coc")->findOneBy(array('url' => $c, 'type' => 'regpol', 'lang' => $k));
                if (null === $existing) {
                    $nregpol = new models\Coc;
                    $nregpol->setUrl($c);
                    $nregpol->setName($c);
                    $nregpol->setType('regpol');
                    $nregpol->setLang($k);
                    $nregpol->setDescription($c);
                    $nregpol->setAvailable(false);
                    $this->em->persist($nregpol);
                }
            }
        }
        $this->em->flush();

        return $this->metaArray;
    }

    public function entitiesConvert(\DOMElement $doc, $full = false) {
        if ($doc->localName === 'EntityDescriptor' && $doc->namespaceURI === 'urn:oasis:names:tc:SAML:2.0:metadata') {
            $this->entityConvert($doc, $full);
        } elseif ($doc->localName === 'EntitiesDescriptor' && $doc->namespaceURI === 'urn:oasis:names:tc:SAML:2.0:metadata' ) {
            $lxpath = new \DomXPath($this->doc);
            foreach ($lxpath->query('namespace::*', $doc) as $pnode) {
                $prefix = $pnode->prefix;
                $val = trim($pnode->nodeValue);
                if (!empty($prefix) && (strcmp($prefix, 'xml') != 0)) {
                    $this->newNameSpaces['' . $prefix . ''] = $val;
                }
            }
            $namespaces = h_metadataNamespaces();
            $this->newNameSpaces = array_diff_assoc($this->newNameSpaces, $namespaces);

            if (count($this->newNameSpaces)) {
                log_message('warning', __METHOD__ . ' Found additional xmlns not known by system ' . serialize($this->newNameSpaces));
                foreach ($this->newNameSpaces as $k => $v) {
                    $this->xpath->registerNamespace($k, $v);
                }
            }
            if ($doc->hasChildNodes()) {
                foreach ($doc->childNodes as $child) {
                    if ($child instanceof \DOMElement) {
                        $this->entitiesConvert($child, $full);
                    }
                }
            }
        } else {
            return;
        }
    }

    public function entityDOMToArray(\DOMElement $node, $full = false) {
        /**
         * @todo fi ix
         */
        $this->doc = new \DOMDocument();


        $this->entityConvert($node, $full);

        return $this->metaArray;
    }

    private function entityConvert(\DOMElement $node, $full = false) {

        $isIdp = false;
        $isSp = false;
        $entity = array(
            'metadata' => null,
            'entityid' => $node->getAttribute('entityID'),
            'validuntil' => $node->getAttribute('validUntil'),
            'rigistrar' => null,
            'regdate' => null,
            'coc' => array(),
            'regpol' => array(),
            'algs' => array(),
            'details' => array(
                'org' => array('OrganizationName' => array(), 'OrganizationDisplayName' => array(), 'OrganizationURL' => array()),
                'contacts' => array(),
                'reqattrs' => array(),
                'reqattrsinmeta' => false,
            ),
        );

        foreach ($node->childNodes as $gnode) {

            if ($gnode->localName === 'IDPSSODescriptor') {
                $isIdp = true;
                if ($full === true) {

                    $entity['details']['idpssodescriptor'] = $this->idpSSODescriptorConvert($gnode);
                }
                continue;
            }
            if ($gnode->localName === 'SPSSODescriptor') {
                $isSp = true;
                if ($full === true) {

                    $entity['details']['spssodescriptor'] = $this->spSSODescriptorConvert($gnode);
                }
                foreach ($gnode->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:metadata', 'RequestedAttribute') as $reqattr) {
                    if (strcasecmp($reqattr->getAttribute('NameFormat'), 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri') == 0) {
                        $entity['details']['reqattrs'][] = array('name' => '' . $reqattr->getAttribute('Name') . '',
                            'req' => $reqattr->getAttribute('isRequired'));
                    }
                    $entity['details']['reqattrsinmeta'] = true;
                }
                continue;
            }
            if ($gnode->localName === 'AttributeAuthorityDescriptor') {
                $isIdp = true;
                $entity['details']['aadescriptor'] = $this->attributeAuthorityDescriptorConvert($gnode);
                continue;
            }
            if ($gnode->localName === 'Extensions') {
                if ($gnode->hasChildNodes()) {
                    foreach ($gnode->childNodes as $enode) {

                        if ($enode->localName === 'RegistrationInfo' && $enode->namespaceURI === 'urn:oasis:names:tc:SAML:metadata:rpi' && $enode->hasAttributes()) {
                            $entity['registrar'] = $enode->getAttribute('registrationAuthority');
                            $entity['regdate'] = $enode->getAttribute('registrationInstant');
                            if ($enode->hasChildNodes()) {
                                foreach ($enode->childNodes as $ch) {
                                    if ($ch->localName === 'RegistrationPolicy') {
                                        $chlang = strtolower($ch->getAttribute('xml:lang'));
                                        $chvalue = trim($ch->nodeValue);
                                        if (!empty($chlang) && !empty($chvalue)) {
                                            $entity['regpol'][] = array('lang' => $chlang, 'url' => $chvalue);
                                            $this->regpollist['' . $chlang . ''][] = $chvalue;
                                        }
                                    }
                                }
                            }
                        } elseif ($enode->localName === 'EntityAttributes' && $enode->namespaceURI === 'urn:oasis:names:tc:SAML:metadata:attribute' && $enode->hasChildNodes()) {
                            foreach ($enode->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:assertion', 'Attribute') as $enode2) {
                                if ($enode2->hasAttributes() && in_array($enode2->getAttribute('Name'), $this->allowedEntcats) && $enode2->hasChildNodes()) {
                                    foreach ($enode2->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:assertion', 'AttributeValue') as $enode3) {
                                        $tmpNodeName = $enode2->getAttribute('Name');
                                        $tmpNodeVal = trim($enode3->nodeValue);
                                        if (!(array_key_exists($tmpNodeName, $entity['coc']) && in_array($tmpNodeVal, $entity['coc']['' . $tmpNodeName . '']))) {
                                            $entity['coc']['' . $tmpNodeName . ''][] = $tmpNodeVal;
                                        } else {
                                            log_message('warning', __METHOD__ . ' found duplicated entity attribue in imported metadata ' . $entity['entityid'] . ' :: ' . $tmpNodeName . ' :  ' . $tmpNodeVal);
                                        }
                                        $this->coclist['' . $tmpNodeName . ''][] = $tmpNodeVal;
                                    }
                                }
                            }
                        } elseif ($enode->localName === 'DigestMethod' && $enode->namespaceURI === 'urn:oasis:names:tc:SAML:metadata:algsupport') {
                            $entity['algs'][] = array(
                                'name' => 'DigestMethod',
                                'algorithm' => $enode->getAttribute('Algorithm'),
                            );
                        } elseif ($enode->localName === 'SigningMethod' && $enode->namespaceURI === 'urn:oasis:names:tc:SAML:metadata:algsupport') {
                            $tmlentry = array(
                                'name' => 'SigningMethod',
                                'algorithm' => $enode->getAttribute('Algorithm'),
                                'minkeysize' => $enode->getAttribute('MinKeySize'),
                                'maxkeysize' => $enode->getAttribute('MaxKeySize'),

                            );
                            $entity['algs'][] = $tmlentry;

                        }
                    }
                }
                continue;
            }
            if ($gnode->localName === 'ContactPerson') {
                $entity['details']['contacts'][] = $this->contactPersonConvert($gnode);
                continue;
            }
            if ($gnode->localName === 'Organization') {
                $entity['details']['org'] = $this->organizationConvert($gnode);
                continue;
            }
        }
        if ($isIdp && $isSp) {
            $entity['type'] = 'BOTH';
        } elseif ($isIdp) {
            $entity['type'] = 'IDP';
        } elseif ($isSp) {
            $entity['type'] = 'SP';
        }

        if ($this->convertNameIdToAttrs && $isSp) {
            if (isset($entity['details']['spssodescriptor']['nameid']) && is_array($entity['details']['spssodescriptor']['nameid']) && count($entity['details']['spssodescriptor']['nameid']) > 0) {
                if (in_array('urn:oasis:names:tc:SAML:2.0:nameid-format:persistent', $entity['details']['spssodescriptor']['nameid']) && array_key_exists('persistentId', $this->nameidsattrs)) {
                    $entity['details']['reqattrs'][] = array('name' => $this->nameidsattrs['persistentId'], 'req' => 'True');
                }
                if (in_array('urn:oasis:names:tc:SAML:2.0:nameid-format:transient', $entity['details']['spssodescriptor']['nameid']) && array_key_exists('transientId', $this->nameidsattrs)) {
                    $entity['details']['reqattrs'][] = array('name' => $this->nameidsattrs['transientId'], 'req' => 'True');
                }
            } elseif (array_key_exists('transientId', $this->nameidsattrs)) {
                $entity['details']['reqattrs'][] = array('name' => $this->nameidsattrs['transientId'], 'req' => 'True');
            }
        }
        /**
         * check for duplicates
         */
        if (isset($entity['details']['reqattrs']) && is_array($entity['details']['reqattrs'])) {
            $attrssets = array();
            foreach ($entity['details']['reqattrs'] as $k => $v) {
                if (in_array($v['name'], $attrssets)) {
                    unset($entity['details']['reqattrs']['' . $k . '']);
                } else {
                    $attrssets[] = $v['name'];
                }
            }
        }

        try {
            $entity['metadata'] = $this->doc->saveXML($node);
        } catch (Exception $e) {
            log_message('warning', 'Couldn store xml: ' . $e);
        }
        $this->metaArray[$entity['entityid']] = $entity;
    }

    private function attributeAuthorityDescriptorConvert(\DOMElement $node) {
        $result['protocols'] = array_filter(explode(' ', $node->getAttribute('protocolSupportEnumeration')), 'strlen');
        foreach ($node->childNodes as $child) {
            if ($child->localName === 'Extensions') {
                $result['extensions'] = $this->aaExtensionsToArray($child);
                continue;
            }
            if ($child->localName === 'NameIDFormat') {
                $result['nameid'][] = trim($child->nodeValue);
                continue;
            }
            if ($child->localName === 'AttributeService') {
                $result['attributeservice'][] = array('binding' => $child->getAttribute('Binding'), 'location' => $child->getAttribute('Location'));
                continue;
            }
            if ($child->localName === 'KeyDescriptor') {
                $result['certificate'][] = $this->keyDescriptorConvert($child);
            }
        }

        return $result;
    }

    private function idpSSODescriptorConvert(\DOMElement $node) {
        $result['protocols'] = array_filter(explode(' ', $node->getAttribute('protocolSupportEnumeration')), 'strlen');
        foreach ($node->childNodes as $child) {
            if ($child->localName === 'Extensions') {
                $result['extensions'] = $this->extensionsToArray($child);
                continue;
            }
            if ($child->localName === 'NameIDFormat') {
                $result['nameid'][] = trim($child->nodeValue);
                continue;
            }
            if ($child->localName === 'SingleSignOnService') {
                $result['servicelocations']['singlesignonservice'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location')
                );
                continue;
            }
            if ($child->localName === 'SingleLogoutService') {
                $result['servicelocations']['singlelogout'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location')
                );
                continue;
            }
            if ($child->localName === 'ArtifactResolutionService') {
                $result['servicelocations']['artifactresolutionservice'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location'),
                    'order' => $child->getAttribute('index'),
                    'isdefault' => $child->getAttribute('isDefault')
                );
                continue;
            }
            if ($child->localName === 'KeyDescriptor') {
                $result['certificate'][] = $this->keyDescriptorConvert($child);
            }
        }

        return $result;
    }

    private function spSSODescriptorConvert(\DOMElement $node) {
        $WantAssertSigned = false;
        $tmpWantAssertSigned = $node->getAttribute('WantAssertionsSigned');
        if ($tmpWantAssertSigned === 'true') {
            $WantAssertSigned = true;
        }
        $profilesTmp = $node->getAttribute('protocolSupportEnumeration');
        $profiles = explode(' ', $profilesTmp);
        $result = array(
            'protocols' => $profiles,
            'wantassertsigned' => $WantAssertSigned,
            'servicelocations' => array('assertionconsumerservice' => array(), 'singlelogout' => array()),
            'extensions' => array(
                'idpdisc' => array(),
                'init' => array(),
                'desc' => array()
            ),
        );
        $bindProts = array(
            'singlelogout' => array(),
        );

        foreach ($node->childNodes as $child) {
            if ($child->localName === 'Extensions') {
                $result['extensions'] = $this->extensionsToArray($child);
                continue;
            }
            if ($child->localName === 'NameIDFormat') {
                $result['nameid'][] = trim($child->nodeValue);
                continue;
            }
            if ($child->localName === 'AssertionConsumerService') {
                $result['servicelocations']['assertionconsumerservice'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location'),
                    'order' => $child->getAttribute('index'),
                    'isdefault' => $child->getAttribute('isDefault')
                );
                continue;
            }
            if ($child->localName === 'ArtifactResolutionService') {
                $result['servicelocations']['artifactresolutionservice'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location'),
                    'order' => $child->getAttribute('index'),
                    'isdefault' => $child->getAttribute('isDefault')
                );
                continue;
            }
            if ($child->localName === 'SingleLogoutService') {
                $bindProto = trim($child->getAttribute('Binding'));
                if (!in_array($bindProto, $bindProts['singlelogout'])) {
                    $result['servicelocations']['singlelogout'][] = array(
                        'binding' => $bindProto,
                        'location' => trim($child->getAttribute('Location'))
                    );
                    $bindProts['singlelogout'][] = $bindProto;
                }
                continue;
            }
            if ($child->localName === 'ManageNameIDService') {
                $result['servicelocations']['managenameidservice'][] = array(
                    'binding' => $child->getAttribute('Binding'),
                    'location' => $child->getAttribute('Location')
                );
                continue;
            }
            if ($child->localName === 'KeyDescriptor') {
                $result['certificate'][] = $this->keyDescriptorConvert($child);
            }
        }

        return $result;
    }

    private function keyDescriptorConvert(\DOMElement $node) {
        $cert = array();
        $usecase = $node->getAttribute('use');
        $cert['use'] = $usecase;
        foreach ($node->childNodes as $child) {

            if ($child->localName === 'KeyInfo' && $child->namespaceURI === 'http://www.w3.org/2000/09/xmldsig#') {
                foreach ($child->childNodes as $gchild) {
                    if ($gchild->localName === 'KeyName' && $gchild->namespaceURI === 'http://www.w3.org/2000/09/xmldsig#') {
                        $cert['keyname'][] = $gchild->nodeValue;
                    } elseif ($gchild->localName === 'X509Data' && $gchild->namespaceURI === 'http://www.w3.org/2000/09/xmldsig#') {

                        foreach ($gchild->childNodes as $enode) {
                            if ($enode->localName === 'X509Certificate' && $enode->namespaceURI === 'http://www.w3.org/2000/09/xmldsig#') {
                                if (!empty($enode->nodeValue)) {
                                    $cert['x509data']['x509certificate'] = reformatPEM($enode->nodeValue);
                                } else {
                                    $cert['x509data']['x509certificate'] = null;
                                }
                            }
                        }
                    }
                }
            } elseif ($child->localName === 'EncryptionMethod' && $child->namespaceURI === 'urn:oasis:names:tc:SAML:2.0:metadata') {
                $cert['encmethods'][] = $child->getAttribute('Algorithm');
            }
        }

        return $cert;
    }

    private function aaExtensionsToArray(\DOMElement $node) {
        $result = array();
        foreach ($node->childNodes as $enode) {
            if ($enode->localName === 'Scope' && $enode->namespaceURI === 'urn:mace:shibboleth:metadata:1.0') {
                $result['aascope'][] = trim($enode->nodeValue);
            }
        }

        return $result;
    }

    private function extensionsToArray(\DOMElement $node) {
        $ext = array();
        foreach ($node->childNodes as $enode) {
            if ($enode->localName === 'Scope' && $enode->namespaceURI === 'urn:mace:shibboleth:metadata:1.0') {
                $ext['scope'][] = trim($enode->nodeValue);
                continue;
            }
            if ($enode->localName === 'DiscoveryResponse' && $enode->namespaceURI === 'urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol') {
                $ext['idpdisc'][] = array('binding' => $enode->getAttribute('Binding'), 'url' => $enode->getAttribute('Location'), 'order' => $enode->getAttribute('index'));
                continue;
            }
            if ($enode->localName === 'RequestInitiator'  && $enode->namespaceURI === 'urn:oasis:names:tc:SAML:profiles:SSO:request-init') {
                $ext['init'][] = array('binding' => $enode->getAttribute('Binding'), 'url' => $enode->getAttribute('Location'));
                continue;
            }
            if ($enode->localName === 'UIInfo' && $enode->namespaceURI === 'urn:oasis:names:tc:SAML:metadata:ui' && $enode->hasChildNodes()) {

                $tmpMapping = array(
                    'Description' => 'desc',
                    'DisplayName' => 'displayname',
                    'PrivacyStatementURL' => 'privacyurl',
                    'InformationURL' => 'informationurl',
                    'Logo' => 'logo',
                    'Keywords' => 'keywords'
                );
                foreach ($enode->childNodes as $gnode) {
                    if (in_array($gnode->localName, array('Description', 'DisplayName', 'PrivacyStatementURL', 'InformationURL', 'Keywords'), true)) {
                        $keynodeName = $tmpMapping['' . $gnode->localName . ''];
                        $ext['' . $keynodeName . ''][] = array('lang' => $gnode->getAttribute('xml:lang'), 'val' => trim($gnode->nodeValue));
                        continue;
                    }
                    if ($gnode->localName === 'Logo') {
                        $logoval = trim($gnode->nodeValue);
                        $ext['logo'][] = array('height' => $gnode->getAttribute('height'), 'width' => $gnode->getAttribute('width'), 'xml:lang' => $gnode->getAttribute('xml:lang'), 'val' => $logoval);

                    }
                }
                continue;
            }
            if ($enode->localName === 'DiscoHints' && $enode->namespaceURI === 'urn:oasis:names:tc:SAML:metadata:ui' && $enode->hasChildNodes()) {
                foreach ($enode->childNodes as $agnode) {
                    if ($agnode->localName === 'GeolocationHint') {
                        $geovalue = $this->convertGeoToArray($agnode->nodeValue);
                        if (is_array($geovalue)) {
                            $ext['geo'][] = $geovalue;
                        }
                    } elseif ($agnode->localName === 'IPHint') {
                        $ext['iphint'][] = trim($agnode->nodeValue);
                    } elseif ($agnode->localName === 'DomainHint') {
                        $ext['domainhint'][] = trim($agnode->nodeValue);
                    }
                }
            }
        }

        return $ext;
    }

    /**
     * @param $val
     * @return array|null
     */
    private function convertGeoToArray($val) {
        $geovalue = explode(',', str_ireplace('geo:', '', $val));
        if (count($geovalue) == 2) {
            $numericvalues = true;
            foreach ($geovalue as $g) {
                if (!is_numeric($g)) {
                    $numericvalues = false;
                }
            }
            if ($numericvalues === true) {
                return array_values($geovalue);
            }
        }
        return null;
    }

    private function organizationConvert(\DOMElement $node) {
        $org = array('OrganizationName' => array(), 'OrganizationDisplayName' => array(), 'OrganizationURL' => array());
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                if (!$child instanceOf DOMText) {
                    $org['' . $child->localName . '']['' . $child->getAttribute('xml:lang') . ''] = trim($child->nodeValue);
                }
            }
        }

        return $org;
    }

    private function contactPersonConvert(\DOMElement $node) {
        $isSirfty = false;
        if ($node->hasAttributeNS('http://refeds.org/metadata', 'contactType')) {
            $isSirfty = true;
        }
        $cnt = array(
            'type' => $node->getAttribute('contactType'),
            'surname' => null,
            'givenname' => null,
            'email' => null,
            'issirfti' => $isSirfty
        );
        foreach ($node->childNodes as $cnode) {
            if ($cnode->localName === 'SurName') {
                $cnt['surname'] = trim($cnode->nodeValue);
                continue;
            }
            if ($cnode->localName === 'GivenName') {
                $cnt['givenname'] = trim($cnode->nodeValue);
                continue;
            }
            if ($cnode->localName === 'EmailAddress') {
                $cnt['email'] = trim($cnode->nodeValue);
                continue;
            }
        }

        return $cnt;
    }

}
