<?php


/**
 * @propoerty Arpgen $arpgen
 */

/**
 * Class Attributepolicy2
 */
class Attributepolicy2 extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param null $idpid
     * @return bool
     */
    private function initiateAjaxAccess($idpid = null)
    {
        return (ctype_digit($idpid) && $this->input->is_ajax_request() && $this->j_auth->logged_in());
    }
    private function getEntity($idpid)
    {
        $ent =  $this->em->getRepository('models\Provider')->findOneBy(array('id' => $idpid,'type'=>array('IDP','BOTH')));
        return $ent;
    }

    public function show($idpid = null)
    {
        if (!ctype_digit($idpid)) {
            show_404();
        }
        if (!$this->j_auth->logged_in()) {
            redirect('auth/login', 'location');
        }

        /**
         * @var $ent models\Provider
         */
        $ent = $this->getEntity($idpid);
        if ($ent === null) {
            show_404();
        }
        $myLang = MY_Controller::getLang();
        $providerNameInLang = $ent->getNameToWebInLang($myLang, 'idp');
        $this->load->library('zacl');
        $hasWriteAccess = $this->zacl->check_acl($idpid, 'write', 'entity', '');
        if (!$hasWriteAccess) {
            show_error('Denied', 401);
        }
        $this->load->library('arpgen');
        $data['breadcrumbs'] = array(
            array('url' => base_url('providers/idp_list/showlist'), 'name' => lang('identityproviders')),
            array('url' => base_url('providers/detail/show/' . $idpid . ''), 'name' => '' . $providerNameInLang . ''),
            array('url' => '#', 'name' => lang('rr_attributereleasepolicy'), 'type' => 'current'),
        );
        $data['attrdefs'] = $this->arpgen->getAttrDefs();
        $data['arpglobal'] = $this->arpgen->genGlobal($ent);
        $data['arpsupport'] = $this->arpgen->getSupportAttributes($ent);
        $data['idpid'] = $ent->getId();
        $data['content_view'] = 'manage/attributepolicy2_view';
        $this->load->view('page', $data);
    }

    public function getsupported($idpid = null)
    {
        if (!$this->initiateAjaxAccess($idpid)) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }


        /**
         * @var $ent models\Provider
         */
        $ent = $this->em->getRepository('models\Provider')->findOneBy(array('id' => $idpid));
        if ($ent === null) {
            return $this->output->set_status_header(404)->set_output('Not found');
        }
        $this->load->library('zacl');
        $hasWriteAccess = $this->zacl->check_acl($idpid, 'write', 'entity', '');
        if (!$hasWriteAccess) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }

        $this->load->library('arpgen');


        $result['type'] = 'supported';
        $result['definitions']['columns'] = array(lang('attrname'), lang('dfltarpcolname'), lang('rr_action'));
        $result['data']['support'] = $this->arpgen->getSupportAttributes($ent);
        $result['data']['global'] = $this->arpgen->genGlobal($ent);
        $result['definitions']['attrs'] = $this->arpgen->getAttrDefs();
        $result['definitions']['policy'] = array('0' => lang('dropnever'), '1' => lang('dropokreq'), '2' => lang('dropokreqdes'), '100' => lang('dropnotset'), '1000' => 'unsupported');

        return $this->output->set_content_type('application/json')->set_output(json_encode($result));

    }

    public function getentcats($idpid = null)
    {
        if (!$this->initiateAjaxAccess($idpid)) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        /**
         * @var $ent models\Provider
         */
        $ent = $this->em->getRepository('models\Provider')->findOneBy(array('id' => $idpid));
        if ($ent === null) {
            return $this->output->set_status_header(404)->set_output('Not found');
        }
        $this->load->library('zacl');
        $hasWriteAccess = $this->zacl->check_acl($idpid, 'write', 'entity', '');
        if (!$hasWriteAccess) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        /**
         * @var $cocs models\Coc[]
         */
        $cocs = $this->em->getRepository('models\Coc')->findBy(array('type' => 'entcat','subtype'=>'http://macedir.org/entity-category'), array('url' => 'ASC'));
        $entcats = array();
        foreach ($cocs as $cocVal) {
            $entcats[] = array('entcatid' => $cocVal->getId(), 'name' => $cocVal->getSubtype(), 'value' => $cocVal->getUrl(),'display'=>$cocVal->getName());
        }
        $this->load->library('arpgen');
        $result['definitions']['attrs'] = $this->arpgen->getAttrDefs();
        $result['data']['support'] = $this->arpgen->getSupportAttributes($ent);
        $result['data']['entcats'] = $entcats;
        return $this->output->set_content_type('application/json')->set_output(json_encode($result));

    }

    public function getspecattrs($idpid = null)
    {
        if (!$this->initiateAjaxAccess($idpid)) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        /**
         * @var $ent models\Provider
         */
        $ent = $this->em->getRepository('models\Provider')->findOneBy(array('id' => $idpid));
        if ($ent === null) {
            return $this->output->set_status_header(404)->set_output('Not found');
        }
        $this->load->library('zacl');
        $hasWriteAccess = $this->zacl->check_acl($idpid, 'write', 'entity', '');
        if (!$hasWriteAccess) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        $this->load->library('arpgen');
        $tmpSPDefinition = new models\Providers();
        $spsDefinitions = $tmpSPDefinition->getSPsEntities();
        $sps = array();
        foreach($spsDefinitions as $spEnt)
        {
            $sps[$spEnt->getId()]['entityid'] = $spEnt->getEntityId();
        }
        $result['type'] = 'sp';
      //  $result['data']['global'] = $this->arpgen->genGlobal($ent);
      //  $result['data']['support'] = $this->arpgen->getSupportAttributes($ent);
        $result['data'] = $this->arpgen->genPolicyDefs($ent);
        $result['definitions']['columns'] = array(lang('attrname'), 'Policy', 'requirements',lang('rr_action'));
        $result['definitions']['sps'] = $sps;
     //   $result['definitions']['attrs'] = $this->arpgen->getAttrDefs();

        $result['definitions']['policy'] = array('0' => lang('dropnever'), '1' => lang('dropokreq'), '2' => lang('dropokreqdes'), '100' => lang('dropnotset'), '1000' => 'unsupported');
        return $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

    public function getentcatattrs($idpid = null)
    {
        if (!$this->initiateAjaxAccess($idpid)) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        /**
         * @var $ent models\Provider
         */
        $ent = $this->getEntity($idpid);
        if ($ent === null) {
            return $this->output->set_status_header(404)->set_output('Not found');
        }
        $this->load->library('zacl');
        $hasWriteAccess = $this->zacl->check_acl($idpid, 'write', 'entity', '');
        if (!$hasWriteAccess) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        $this->load->library('arpgen');
        $result['type'] = 'entcat';
        $result['data']['global'] = $this->arpgen->genGlobal($ent);
        $result['data']['support'] = $this->arpgen->getSupportAttributes($ent);
        $result['definitions']['columns'] = array(lang('attrname'), 'Policy', lang('rr_action'));
        $result['definitions']['attrs'] = $this->arpgen->getAttrDefs();
        $result['definitions']['policy'] = array('0' => lang('dropnever'), '1' => lang('dropokreq'), '2' => lang('dropokreqdes'), '100' => lang('dropnotset'), '1000' => 'unsupported');

        $cocsColl = $this->em->getRepository('models\Coc')->findBy(array('type' => 'entcat'));
        $entcats = array();
        foreach ($cocsColl as $entcat) {
            $entcats[$entcat->getId()] = array('name' => $entcat->getSubtype(), 'value' => $entcat->getUrl());
        }

        $result['definitions']['entcats'] = $entcats;
        /**
         * @var $entcatPoliciesColl models\AttributeReleasePolicy[]
         */
        $entcatPoliciesColl = $this->em->getRepository('models\AttributeReleasePolicy')->findBy(array('idp' => $ent, 'type' => 'entcat'));
        $entcatPolicies = array();
        foreach ($entcatPoliciesColl as $encatpol) {
            $requester = $encatpol->getRequester();
            if (empty($requester) || $requester === '0' || !array_key_exists($requester, $entcats)) {
                $this->em->remove($encatpol);
                continue;
            }
            $entcatPolicies[$requester][$encatpol->getAttribute()->getId()] = $encatpol->getPolicy();
        }
        $result['data']['entcats'] = $entcatPolicies;


        $this->em->flush();
        return $this->output->set_content_type('application/json')->set_output(json_encode($result));

    }

    public function addattrentcat($idpid = null)
    {
        if (!$this->initiateAjaxAccess($idpid)) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        /**
         * @var $ent models\Provider
         */
        $ent = $this->em->getRepository('models\Provider')->findOneBy(array('id' => $idpid));
        if ($ent === null) {
            return $this->output->set_status_header(404)->set_output('Not found');
        }
        $this->load->library('zacl');
        $hasWriteAccess = $this->zacl->check_acl($idpid, 'write', 'entity', '');
        if (!$hasWriteAccess) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        $entcatid = trim($this->input->post('entcatid'));
        $attrid = trim($this->input->post('attrid'));
        $policy = trim($this->input->post('policy'));
        if (!ctype_digit($entcatid) || !ctype_digit($attrid) || !ctype_digit($policy) || !in_array($policy, array('0', '1', '2'))) {
            return $this->output->set_status_header(403)->set_output('Posted invalid data');
        }
        $attribute =  $this->em->getRepository('models\Attribute')->findOneBy(array('id'=>$attrid));
        $entcategory = $this->em->getRepository('models\Coc')->findOneBy(array('id'=>$entcatid,'type'=>'entcat'));
        if($attribute === null || $entcategory === null)
        {
            return $this->output->set_status_header(403)->set_output('Attribute or EntityCategory not found');
        }
        /**
         * @var $findPolicy models\AttributeReleasePolicy
         */
        $findPolicy = $this->em->getRepository('models\AttributeReleasePolicy')->findOneBy(array('attribute'=>$attrid,'idp'=>$ent,'type'=>'entcat','requester'=>$entcatid));
        if($findPolicy === null)
        {
            $findPolicy = new models\AttributeReleasePolicy();
            $findPolicy->setEntCategoryPolicy($ent,$attribute,$entcatid,$policy);
        }
        else
        {
            $findPolicy->setPolicy($policy);
        }
        $this->em->persist($findPolicy);

        try{
            $this->em->flush();
            return $this->output->set_content_type('application/json')->set_output(json_encode(array('status' => 'success')));
        }
        catch(Exception $e)
        {
            log_message('error',__METHOD__.' '.$e);
            return $this->output->set_status_header(500)->set_output('Internal Server Error');
        }


    }

    public function getfedattrs($idpid = null)
    {
        if (!$this->initiateAjaxAccess($idpid)) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }


        /**
         * @var $ent models\Provider
         */
        $ent = $this->em->getRepository('models\Provider')->findOneBy(array('id' => $idpid));
        if ($ent === null) {
            return $this->output->set_status_header(404)->set_output('Not found');
        }
        $this->load->library('zacl');
        $hasWriteAccess = $this->zacl->check_acl($idpid, 'write', 'entity', '');
        if (!$hasWriteAccess) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        $this->load->library('arpgen');
        $result['type'] = 'federation';

        // $result['policydefs'] = $this->arpgen->genPolicyDefs($ent);
        $result['definitions']['policy'] = array('0' => lang('dropnever'), '1' => lang('dropokreq'), '2' => lang('dropokreqdes'), '100' => lang('dropnotset'), '1000' => 'unsupported');
        $result['definitions']['columns'] = array(lang('attrname'), 'policy', 'reqstatus', lang('rr_action'));
        $result['definitions']['lang']['federation'] = lang('rr_federation');
        $result['definitions']['lang']['unsupported'] = 'unsupported';
        $result['data']['support'] = $this->arpgen->getSupportAttributes($ent);
        $result['data']['global'] = $this->arpgen->genGlobal($ent);

        /**
         * @var $allFeds models\Federation[]
         */
        $allFeds = $this->em->getRepository('models\Federation')->findAll();
        $allFederations = array();
        foreach ($allFeds as $fed) {
            $allFederations[$fed->getId()] = $fed->getName();

        }
        /**
         * @var $fedpolicies models\AttributeReleasePolicy[]
         */
        $fedpolicies = $this->em->getRepository('models\AttributeReleasePolicy')->findBy(
            array(
                'idp' => $ent,
                'type' => array('fed')
            )
        );
        $fedpoliciesByRequester = array();
        foreach ($fedpolicies as $fedpolicy) {
            $requester = $fedpolicy->getRequester();
            if (!array_key_exists($requester, $allFederations)) {
                log_message('warning', __METHOD__ . ' found policy for federation which doesn not exist anymore - policy will be removed automaticaly');
                $this->em->remove($fedpolicy);
                continue;
            }
            $fedpoliciesByRequester[$fedpolicy->getRequester()][$fedpolicy->getAttribute()->getId()] = $fedpolicy->getPolicy();
        }


        $result['definitions']['feds'] = $allFederations;
        $result['definitions']['attrs'] = $this->arpgen->getAttrDefs();
        $result['data']['fedpols'] = $fedpoliciesByRequester;

        $activeFederation = $this->arpgen->getActiveFederations($ent);

        $result['data']['activefeds'] = $activeFederation;
        foreach($activeFederation as $fid)
        {
            if(!array_key_exists($fid, $result['data']['fedpols']))
            {
                $result['data']['fedpols'][$fid] = array();
            }
        }

        $result['definitions']['statusstr']['inactive'] = 'inactive';

        $this->em->flush();
        return $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }


    public function delattr($idpid = null)
    {
        if (!$this->initiateAjaxAccess($idpid)) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        $ent = $this->em->getRepository('models\Provider')->findOneBy(array('id' => $idpid));
        if ($ent === null) {
            return $this->output->set_status_header(404)->set_output('Not found');
        }
        $this->load->library('zacl');
        $hasWriteAccess = $this->zacl->check_acl($idpid, 'write', 'entity', '');
        if (!$hasWriteAccess) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }

        $attrid = $this->input->post('attrid');
        if (!ctype_digit($attrid)) {
            return $this->output->set_status_header(403)->set_output('Posted invalid data');
        }
        $policies = $this->em->getRepository('models\AttributeReleasePolicy')->findBy(array('idp' => $ent->getId(), 'attribute' => $attrid));
        foreach ($policies as $policy) {
            $this->em->remove($policy);
        }
        try {
            $this->em->flush();
            return $this->output->set_content_type('application/json')->set_output(json_encode(array('status' => 'success')));
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            return $this->output->set_status_header(500)->set_output('Internal server error');
        }

    }


    public function updateattrglobal($idpid = null)
    {
        if (!$this->initiateAjaxAccess($idpid)) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        /**
         * @var $ent models\Provider
         */
        $ent = $this->em->getRepository('models\Provider')->findOneBy(array('id' => $idpid));
        if ($ent === null) {
            return $this->output->set_status_header(404)->set_output('Not found');
        }
        $this->load->library('zacl');
        $hasWriteAccess = $this->zacl->check_acl($idpid, 'write', 'entity', '');
        if (!$hasWriteAccess) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }

        $attrid = trim($this->input->post('attrid'));
        $policy = trim($this->input->post('policy'));
        $supportintput = trim($this->input->post('support'));
        if (!ctype_digit($attrid) || !ctype_digit($policy) || !in_array($policy, array('0', '1', '2', '100'))) {
            return $this->output->set_status_header(403)->set_output('Posted invalid data');
        }
        /**
         * @var $attribute models\Attribute
         */
        $attribute = $this->em->getRepository('models\Attribute')->findOneBy(array('id' => $attrid));
        if ($attribute === null) {
            return $this->output->set_status_header(403)->set_output('Attribute not found');
        }
        $supAttr = $this->em->getRepository('models\AttributeReleasePolicy')->findOneBy(array('attribute' => $attrid, 'idp' => $ent->getId(), 'type' => 'supported'));
        if ($supportintput === 'enabled' && $supAttr === null) {
            $supAttr = new models\AttributeReleasePolicy();
            $supAttr->setSupportedAttribute($ent, $attribute);
            $this->em->persist($supAttr);
        } elseif (empty($supportintput) && $supAttr !== null) {
            $this->em->remove($supAttr);
        }
        /**
         * @var $globAttr models\AttributeReleasePolicy
         */
        $globAttr = $this->em->getRepository('models\AttributeReleasePolicy')->findOneBy(array('attribute' => $attrid, 'idp' => $ent->getId(), 'type' => 'global'));

        if ($policy === '100') {
            if ($globAttr !== null) {
                $this->em->remove($globAttr);
            }
        } else {
            if ($globAttr !== null) {
                $globAttr->setPolicy($policy);
                $this->em->persist($globAttr);
            } else {
                $globAttr = new models\AttributeReleasePolicy();
                $globAttr->setGlobalPolicy($ent, $attribute, $policy);
                $this->em->persist($globAttr);
            }
        }

        try {
            $this->em->flush();
            return $this->output->set_content_type('application/json')->set_output(json_encode(array('status' => 'success')));
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            return $this->output->set_status_header(500)->set_output('Internal server error');
        }

    }

    public function updateattrentcat($idpid = null)
    {
        if (!$this->initiateAjaxAccess($idpid)) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        $ent = $this->em->getRepository('models\Provider')->findOneBy(array('id' => $idpid, 'type' => array('IDP', 'BOTH')));
        if ($ent === null) {
            return $this->output->set_status_header(404)->set_output('Not found');
        }
        $this->load->library('zacl');
        $hasWriteAccess = $this->zacl->check_acl($idpid, 'write', 'entity', '');
        if (!$hasWriteAccess) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        $attrid = trim($this->input->post('attrid'));
        $policy = trim($this->input->post('policy'));
        $entcatid = trim($this->input->post('entcatid'));
        if (!ctype_digit($attrid) || !ctype_digit($policy) || !ctype_digit($entcatid) || !in_array($policy, array('0', '1', '2', '100'))) {
            return $this->output->set_status_header(403)->set_output('Posted invalid data');
        }
        /**
         * @var $attribute models\Attribute
         */
        $attribute = $this->em->getRepository('models\Attribute')->findOneBy(array('id' => $attrid));
        if ($attribute === null) {
            return $this->output->set_status_header(403)->set_output('Attribute not found');
        }
        /**
         * @todo add select by type(entitycategory)
         * @var $entCategory models\Coc
         */
        $entCategory = $this->em->getRepository('models\Coc')->findOneBy(array('id' => $entcatid));
        if ($entCategory === null) {
            return $this->output->set_status_header(403)->set_output('EntityCategory not found');
        }
        /**
         * @var $attrPolicy models\AttributeReleasePolicy
         */
        $attrPolicy = $this->em->getRepository('models\AttributeReleasePolicy')->findOneBy(array('attribute' => $attrid, 'idp' => $ent->getId(), 'type' => 'entcat', 'requester' => $entcatid));
        if ($policy === '100') {
            if ($attrPolicy !== null) {
                $this->em->remove($attrPolicy);
            }
        } elseif ($attrPolicy === null) {
            $attrPolicy = new models\AttributeReleasePolicy();
            $attrPolicy->setEntCategoryPolicy($ent, $attribute, $entcatid, $policy);
            $this->em->persist($attrPolicy);
        } else {
            $attrPolicy->setPolicy($policy);
            $this->em->persist($attrPolicy);
        }

        try {
            $this->em->flush();
            return $this->output->set_content_type('application/json')->set_output(json_encode(array('status' => 'success')));
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            return $this->output->set_status_header(500)->set_output('Internal Server Error');
        }
    }

    public function updateattrfed($idpid = null)
    {

        if (!$this->initiateAjaxAccess($idpid)) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        $ent = $this->getEntity($idpid);
        if ($ent === null) {
            return $this->output->set_status_header(404)->set_output('Not found');
        }
        $this->load->library('zacl');
        $hasWriteAccess = $this->zacl->check_acl($idpid, 'write', 'entity', '');
        if (!$hasWriteAccess) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        $attrid = trim($this->input->post('attrid'));
        $policy = trim($this->input->post('policy'));
        $fedid = trim($this->input->post('fedid'));
        if (!ctype_digit($attrid) || !ctype_digit($policy) || !ctype_digit($fedid) || !in_array($policy, array('0', '1', '2', '100'))) {
            return $this->output->set_status_header(403)->set_output('Posted invalid data');
        }
        /**
         * @var $attribute models\Attribute
         */
        $attribute = $this->em->getRepository('models\Attribute')->findOneBy(array('id' => $attrid));
        if ($attribute === null) {
            return $this->output->set_status_header(403)->set_output('Attribute not found');
        }
        /**
         * @var $federation models\Federation
         */
        $federation = $this->em->getRepository('models\Federation')->findOneBy(array('id' => $fedid));

        /**
         * @var $attrPolicy models\AttributeReleasePolicy
         */
        $attrPolicy = $this->em->getRepository('models\AttributeReleasePolicy')->findOneBy(array('attribute' => $attrid, 'idp' => $ent->getId(), 'type' => 'fed', 'requester' => $fedid));
        if ($policy === '100') {
            if ($attrPolicy !== null) {
                $this->em->remove($attrPolicy);
            }
        } elseif ($attrPolicy === null) {
            $attrPolicy = new models\AttributeReleasePolicy();
            if ($federation === null) {
                return $this->output->set_status_header(403)->set_output('Federation not found');
            }
            $attrPolicy->setFedPolicy($ent, $attribute, $federation, $policy);
            $this->em->persist($attrPolicy);
        } else {
            $attrPolicy->setPolicy($policy);
            $this->em->persist($attrPolicy);
        }


        try {
            $this->em->flush();
            return $this->output->set_content_type('application/json')->set_output(json_encode(array('status' => 'success')));
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            return $this->output->set_status_header(500)->set_output('Internal Server Error');
        }

    }

}
