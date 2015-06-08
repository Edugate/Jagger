<?php

/**
 * Created by PhpStorm.
 * User: januszu
 * Date: 05/06/15
 * Time: 09:09
 */
class Arpgen
{

    protected $CI;
    /**
     * @var $ent \models\Provider
     */
    protected $ent;
    protected $em;
    /**
     * @var $attrsDefs models\Attribute[]
     */
    protected $attrsDefs;
    protected $attrDefsSmplArray = array();
    protected $supportAttributes;
    protected $tempARPolsInstance;
    protected $entityCategories = array();
    protected $federations = array();

    public function __construct(array $args)
    {
        $this->CI = &get_instance();
        $this->em = $this->CI->doctrine->em;
        if (!array_key_exists('ent', $args) || !($args['ent'] instanceof \models\Provider)) {
            throw new Exception('Missing provider');
        }
        $this->ent = $args['ent'];

        $tmpAttrs = new models\Attributes();
        $this->attrsDefs = $tmpAttrs->getAttributesToArrayById();
        foreach ($this->attrsDefs as $k => $v) {
            $this->attrDefsSmplArray[$k] = $v->getName();
        }
        $this->tempARPolsInstance = new models\AttributeReleasePolicies;
        /**
         * @var $supportAttrColl \models\AttributeReleasePolicy[]
         */
        $supportAttrColl = $this->tempARPolsInstance->getSupportedAttributes($this->ent);
        foreach ($supportAttrColl as $attr) {
            $this->supportAttributes[] = $attr->getAttribute()->getId();
        }
        /**
         * @var $entcats models\Coc[]
         */
        $entcats = $this->em->getRepository('models\Coc')->findBy(array('type'=>'entcat'));
        foreach($entcats as $e)
        {
            $this->entityCategories[$e->getId()] = array('value'=>$e->getUrl(),'name'=>$e->getSubtype());
        }

        /**
         * @var $federations models\Federation[]
         */
        $membership = $this->ent->getActiveFederations();
        foreach($membership as $mm)
        {
            $isActiveFed = $mm->getActive();
            if($isActiveFed)
            {
                $this->federations[] = $mm->getId();
            }
        }


    }


    private function getPoliciec()
    {
        /**
         * @var $policies models\AttributeReleasePolicy[]
         */
        $policies = $this->em->getRepository('models\AttributeReleasePolicy')->findBy(
            array(
                'idp'=>$this->ent,
                'attribute'=>$this->supportAttributes,
                'type'=>array('fed','sp','entcat','customsp')
            )
        );
        $result = array('fed'=>array(),'sp'=>array(),'entcat'=>array(),'customsp'=>array());
        foreach($policies as $entry)
        {
            $entryType = $entry->getType();
            $valuePolicy = $entry->getPolicy();
            if($entryType === 'customsp')
            {
                $valuePolicy= $entry->getRawdata();
            }
            elseif($entryType === 'fed' && !in_array($entry->getRequester(),$this->federations))
            {
                continue;
            }

            $result[$entry->getType()][$entry->getAttribute()->getId()][$entry->getRequester()] = $valuePolicy;
        }
        return $result;
    }
    public function getRequirements($sps)
    {
        $tempAttrReqs = new models\AttributeRequirements;

        $result = array();

        $res = $tempAttrReqs->getRequirementsBySPs($sps);
        foreach ($res as $k => $v) {
            foreach ($v as $req) {
                $result[$k]['req'][$req->getAttribute()->getId()] = $req->getStatusToInt();
            }
        }
        return $result;
    }

    public function getSupportAttributes()
    {
        return $this->supportAttributes;
    }

    private function genGlobal()
    {
        $globals = $this->tempARPolsInstance->getGlobalPolicyAttributes($this->ent);
        $result = array();
        foreach ($globals as $g) {
            $result[$g->getAttribute()->getId()] = $g->getPolicy();
        }
        foreach ($this->supportAttributes as $v) {
            if (!array_key_exists($v, $result)) {
                $result[$v] = 0;
            }
        }
        return $result;
    }

    private function genPolicyByEC()
    {
        /**
         * @var $pols models\AttributeReleasePolicy[]
         */
        $pols = $this->em->getRepository('models\AttributeReleasePolicy')->findBy(array('idp'=>$this->ent,'type'=>'entcat'));
        $result = array();
        foreach($pols as $val)
        {

            $requester = $val->getRequester();
            if($requester !== null) {
                $result[$val->getAttribute()->getId()][$requester] = $val->getPolicy();
            }
        }
        return $result;
    }


    public function genPolicyDefs()
    {

        $result = array(
            'definitions'=>array(
                'attrs'=>  $this->attrDefsSmplArray,
                'ec' => $this->entityCategories,
            ),
            'memberof' => $this->federations,
            'supported' =>  $this->supportAttributes,
            'global' =>  $this->genGlobal(),
        );



        $policies = $this->getPoliciec();
        /**
         * @var $members models\Provider[]
         */
        $members = $this->getMembers($this->ent->getExcarps());

        $result['ecPolicies'] = $policies['entcat'];
        $result['fedPolicies'] = $policies['fed'];
        $result['sps'] = array();
        $membersIDs = array();
        foreach ($members as $p) {
            $membersIDs[] = $p->getId();
        }
;
        $result['sps'] = array_fill_keys($membersIDs, array('entcat' => array(), 'req' => array(), 'feds' => array()));
        foreach ($members as $p) {


            $pid = $p->getId();
            $result['sps'][$pid]['type'] = $p->getType();
            $result['sps'][$pid]['entid'] = $p->getId();
            $feds = $p->getActiveFederations();
            foreach($feds as $f)
            {
                $result['sps'][$pid]['feds'][] = $f->getId();
            }
            // start entityCategory

            $pp = $p->getCoc();
            foreach ($pp as $d) {
                $t = $d->getType();
                if ($t === 'entcat') {
                    $result['sps'][$pid]['entcat'][] = $d->getId();
                }
            }
            // end entityCategory
        }

        // get required attrs by all SP and fille reseult 'req'
        $req = $this->getRequirements(array_keys($result['sps']));
        foreach($req as $kreq => $kvalu)
        {
            $result['sps'][$kreq]['req'] = $kvalu['req'];
        }

        return $result;


    }

    private function getMembers(array $exclude)
    {
        $tempProviders = new models\Providers;
        $members = $tempProviders->getSPsForArpIncEntCats($this->ent, $exclude);
        return $members;
    }

    public function genXML()
    {
        $policy = $this->genPolicyDefs();

        $xml = $this->createXMLHead();


        $xml->startElementNs('afp','AttributeFilterPolicyGroup','urn:mace:shibboleth:2.0:afp');
        $xml->startAttribute('id');
        $xml->text('policy');
        $xml->endAttribute();

        $xml->startAttributeNs('xsi','schemaLocation','http://www.w3.org/2001/XMLSchema-instance');
        $xml->text('urn:mace:shibboleth:2.0:afp classpath:/schema/shibboleth-2.0-afp.xsd urn:mace:shibboleth:2.0:afp:mf:basic classpath:/schema/shibboleth-2.0-afp-mf-basic.xsd urn:mace:shibboleth:2.0:afp:mf:saml classpath:/schema/shibboleth-2.0-afp-mf-saml.xsd');
        $xml->endAttribute();



        $xml->startAttribute('xmlns:basic');
        $xml->text('urn:mace:shibboleth:2.0:afp:mf:basic');
        $xml->endAttribute();
//////


        foreach($policy['ecPolicies'] as $lkey => $lval)
        {
            $xml->startElementNs('afp','AttributeFilterPolicy',null);
            $xml->startAttribute('id');
            $xml->text('EntityAttribute-'.$lkey);
            $xml->endAttribute();

            $xml->startElementNs('afp','PolicyRequirementRule',null);
            $xml->startAttributeNs('xsi','type',null);
            $xml->text('saml:EntityAttributeExactMatch');
            $xml->endAttribute();
            $xml->startAttribute('attributeName');
            $xml->text($policy['definitions']['ec'][$lkey]['name']);
            $xml->endAttribute();
            $xml->startAttribute('attributeValue');
            $xml->text($policy['definitions']['ec'][$lkey]['value']);
            $xml->endAttribute();
            $xml->endElement();


            foreach($lval as $attr1ID=>$attrP)
            {
                $xml->startElementNs('afp','AttributeRule',null);
                $xml->startAttribute('attributeID');
                $xml->text($policy['definitions']['attrs'][$attr1ID]);
                $xml->endAttribute();

                if($attr1ID === 0 )
                {
                    $xml->startElementNs('afp','DenyValueRule',null);
                    $xml->startAttributeNs('xsi','type',null);
                    $xml->text('basic:ANY');
                    $xml->endAttribute();
                    $xml->endElement();
                }
                elseif($attr1ID === 2)
                {
                    $xml->startElementNs('afp','PermitValueRule',null);
                    $xml->startAttributeNs('xsi','type',null);
                    $xml->text('basic:ANY');
                    $xml->endAttribute();
                    $xml->endElement();
                }
                else
                {
                    $xml->startElementNs('afp','PermitValueRule',null);
                    $xml->startAttributeNs('xsi','type',null);
                    $xml->text('saml:AttributeInMetadata');
                    $xml->endAttribute();
                    $xml->startAttribute('id');
                    $xml->text('PermitRule');
                    $xml->endAttribute();

                    $xml->startAttribute('onlyIfRequired');
                    $xml->text('true');
                    $xml->endAttribute();

                    $xml->startAttribute('matchIfMetadataSilent');
                    $xml->text('true');
                    $xml->endAttribute();
                    $xml->endElement();
                }
                $xml->endElement();

            }

            $xml->endElement();
        }




//////
        $xml->endElement();
        $xml->endDocument();
        return $xml;
    }

    /**
     * @return XMLWriter
     */
    public function createXMLHead()
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString(' ');
        $xml->startDocument('1.0', 'UTF-8');
        return $xml;
    }

}