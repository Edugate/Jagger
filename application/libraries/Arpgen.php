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
    protected $ent;
    /**
     * @var $attrsDefs models\Attribute[]
     */
    protected $attrsDefs;
    protected $attrDefsSmplArray = array();
    protected $supportAttributes;
    protected $tempARPolsInstance;

    public function __construct(array $args)
    {
        $this->CI = &get_instance();
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


    public function genPolicyDefs()
    {
        // attribute names  arrayKey=>attrID, arrayVal=attrName
        $result['attrdefs'] = $this->attrDefsSmplArray;
        // supported arrayValues=>attrIDs
        $result['supported'] = $this->supportAttributes;
        $result['global'] = $this->genGlobal();

        $members = $this->getMembers($this->ent->getExcarps());

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
            // start entityCategory

            $pp = $p->getCoc();
            foreach ($pp as $d) {
                $t = $d->getType();
                if ($t === 'entcat') {
                    $result['sps'][$pid]['entcat'][] = $d->getUrl();
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


        echo '<pre>';
        //print_r($result['sp3']);
       // echo 'REQ'.PHP_EOL;
      //  print_r($req);

        print_r($result);
        echo '</pre>';
        return $result;


    }

    private function getMembers(array $exclude)
    {
        $tempProviders = new models\Providers;
        $members = $tempProviders->getSPsForArpIncEntCats($this->ent, $exclude);
        return $members;
    }


}