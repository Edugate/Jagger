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


    private function checkaccess()
    {

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
        $ent = $this->em->getRepository('models\Provider')->findOneBy(array('id' => $idpid));
        if ($ent === null) {
            show_404();
        }
        $this->load->library('zacl');
        $hasWriteAccess = $this->zacl->check_acl($idpid, 'write', 'entity', '');
        if (!$hasWriteAccess) {
            show_error('Denied', 401);
        }
        $this->load->library('arpgen');
        $data['attrdefs'] = $this->arpgen->getAttrDefs();
        $data['arpglobal'] = $this->arpgen->genGlobal($ent);
        $data['arpsupport'] = $this->arpgen->getSupportAttributes($ent);
        $data['idpid'] = $ent->getId();
        $data['content_view'] = 'manage/attributepolicy2_view';
        $this->load->view('page', $data);
    }

    public function getsupported($idpid = null)
    {
        if (!ctype_digit($idpid) || !$this->input->is_ajax_request() || !$this->j_auth->logged_in()) {
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
        $result['definitions']['policy'] = array('0' => 'never permit', '1' => 'only when required', '2' => 'when required or desired', '100' => 'unset', '1000' => 'unsupported');

        return $this->output->set_content_type('application/json')->set_output(json_encode($result));

    }
    public function getfedattrs($idpid = null)
    {
        if (!ctype_digit($idpid) || !$this->input->is_ajax_request() || !$this->j_auth->logged_in()) {
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
$result['definitions']['policy'] = array('0' => 'never permit', '1' => 'only when required', '2' => 'when required or desired', '100' => 'unset', '1000' => 'unsupported');
        $result['definitions']['columns'] = array(lang('attrname'),'policy','reqstatus', lang('rr_action'));
        $result['definitions']['lang']['federation'] = lang('rr_federation');
        $result['data']['support'] = $this->arpgen->getSupportAttributes($ent);
        $result['data']['global'] = $this->arpgen->genGlobal($ent);

        /**
         * @var $allFeds models\Federation[]
         */
        $allFeds = $this->em->getRepository('models\Federation')->findAll();
        $allFederations = array();
        foreach ( $allFeds as $fed) {
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
        foreach($fedpolicies as $fedpolicy)
        {
            $requester = $fedpolicy->getRequester();
            if(!array_key_exists($requester,$allFederations))
            {
                log_message('warning',__METHOD__ .' found policy for federation which doesn not exist anymore - policy will be removed automaticaly');
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

        $result['definitions']['statusstr']['inactive'] = 'inactive';

        $this->em->flush();
        return $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }


    public function delattr($idpid = null)
    {
        if (!ctype_digit($idpid) || !$this->input->is_ajax_request() || !$this->j_auth->logged_in()) {
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
        if(!ctype_digit($attrid))
        {
            return $this->output->set_status_header(403)->set_output('Posted invalid data');
        }
        $policies = $this->em->getRepository('models\AttributeReleasePolicy')->findBy(array('idp'=>$ent->getId(),'attribute'=>$attrid));
        foreach($policies as $policy)
        {
            $this->em->remove($policy);
        }
        try{
            $this->em->flush();
            return $this->output->set_content_type('application/json')->set_output(json_encode(array('status'=>'success')));
        }
        catch(Exception $e)
        {
            log_message('error',__METHOD__.' '.$e);
            return $this->output->set_status_header(500)->set_output('Internal server error');
        }

    }

    public function updateattrglobal($idpid = null)
    {
        if (!ctype_digit($idpid) || !$this->input->is_ajax_request() || !$this->j_auth->logged_in()) {
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
        if(!ctype_digit($attrid) || !ctype_digit($policy) || !in_array($policy,array('0','1','2','100')))
        {
            return $this->output->set_status_header(403)->set_output('Posted invalid data');
        }
        /**
         * @var $attribute models\Attribute
         */
        $attribute = $this->em->getRepository('models\Attribute')->findOneBy(array('id'=>$attrid));
        if($attribute === null)
        {
            return $this->output->set_status_header(403)->set_output('Attribute not found');
        }
        $supAttr = $this->em->getRepository('models\AttributeReleasePolicy')->findOneBy(array('attribute'=>$attrid, 'idp'=>$ent->getId(),'type'=>'supported'));
        if($supportintput === 'enabled' && $supAttr === null)
        {
            $supAttr = new models\AttributeReleasePolicy();
            $supAttr->setSupportedAttribute($ent,$attribute);
            $this->em->persist($supAttr);
        }
        elseif(empty($supportintput) && $supAttr !== null)
        {
            $this->em->remove($supAttr);
        }
        /**
         * @var $globAttr models\AttributeReleasePolicy
         */
        $globAttr = $this->em->getRepository('models\AttributeReleasePolicy')->findOneBy(array('attribute'=>$attrid, 'idp'=>$ent->getId(),'type'=>'global'));

        if($policy === '100')
        {
            if($globAttr !== null) {
                $this->em->remove($globAttr);
            }
        }
        else{
            if($globAttr !== null)
            {
                $globAttr->setPolicy($policy);
                $this->em->persist($globAttr);
            }
            else
            {
                $globAttr = new models\AttributeReleasePolicy();
                $globAttr->setGlobalPolicy($ent,$attribute,$policy);
                $this->em->persist($globAttr);
            }
        }

        try {
            $this->em->flush();
            return $this->output->set_content_type('application/json')->set_output(json_encode(array('status'=>'success')));
        }
        catch(Exception $e)
        {
            log_message('error',__METHOD__.' '.$e);
            return $this->output->set_status_header(500)->set_output('Internal server error');
        }

    }

}