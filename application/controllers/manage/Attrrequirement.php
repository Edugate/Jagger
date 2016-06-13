<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright Copyright (c) 2012, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Attrrequirement extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('form');
        $loggedin = $this->jauth->isLoggedIn();
        $this->current_site = current_url();
        if (!$loggedin) {
            redirect('auth/login', 'location');
        }
        $this->load->library('zacl');
    }

    public function fed($fedid = null)
    {
        $data['no_new_attr'] = 1;
        $this->title = lang('rr_attributerequirements');
        $data['content_view'] = 'manage/attribute_fed_requirement_view';
        if (empty($fedid) || !is_numeric($fedid)) {
            show_error('Page not found', 404);
            return;
        }
        /**
         * @var models\Federation $fed
         */
        $fed = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $fedid));
        if ($fed === null) {
            show_error('Federation not found', 404);
        }
        $resource = 'f_' . $fed->getId();
        $group = 'federation';
        $hasWriteAccess = $this->zacl->check_acl($resource, 'write', $group, '');
        if (!$hasWriteAccess) {
            $data['content_view'] = 'nopermission';
            $data['error'] = lang('rr_noperm_mngtattrforfed') . ': ' . $fed->getName();
            return $this->load->view(MY_Controller::$page, $data);
        }
        log_message('debug', 'preparing for federation: ' . $fed->getName());

        $add_attr = array();
        $attrs = $this->em->getRepository("models\Attribute")->findAll();
        $alreadyInAttr = array();
        foreach ($attrs as $a_def) {
            $add_attr[$a_def->getId()] = $a_def->getName();
        }
        $attrCollection = $fed->getAttributesRequirement()->getValues();
        if (!empty($attrCollection)) {
            foreach ($attrCollection as $a) {
                $attrId = $a->getAttribute()->getId();

                $alreadyInAttr['' . $attrId . ''] = array(
                    'name' => $a->getAttribute()->getName(),
                    'fullname' => $a->getAttribute()->getFullname(),
                    'urn' => $a->getAttribute()->getUrn(),
                    'oid' => $a->getAttribute()->getOid(),
                    'attr_id' => $attrId,
                    'status' => $a->getStatus(),
                    'reason' => $a->getReason(),
                    'description' => $a->getAttribute()->getDescription()
                );
            }
        }
        $fedurl = base64url_encode($fed->getName());

        $add_attr_final = array_diff_key($add_attr, $alreadyInAttr);
        $data['already_in_attr'] = $alreadyInAttr;
        $data['add_attr_final'] = $add_attr_final;
        $data['fed_name'] = $fed->getName();
        $data['fedid'] = $fed->getId();
        $data['fed_encoded'] = base64url_encode($fed->getName());
        $data['titlepage'] = lang('rr_federation') . ': <a href="' . base_url() . 'federations/manage/show/' . $data['fed_encoded'] . '">' . $data['fed_name'] . '</a>';
        $data['subtitlepage'] = lang('rr_requiredattributes');
         $data['breadcrumbs'] = array(
            array('url' => base_url('federations/manage'), 'name' => lang('rr_federations')),
            array('url' => base_url('federations/manage/show/' . $fedurl . ''), 'name' => '' . $fed->getName() . ''),
            array('url' => '#', 'type' => 'current', 'name' => lang('rr_requiredattributes'))

        );
        $data['head'] = lang('rr_attributerequirements') . ': ' . $fed->getName();
        $this->load->view(MY_Controller::$page, $data);
    }


    private function addFed($federation_id, $attr_req)
    {
        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $federation_id));
        if (!empty($federation) && !empty($attr_req)) {
            $attr_req->setFed($federation);
            $federation->setAttributesRequirement($attr_req);
            $this->em->persist($federation);
            $this->em->persist($attr_req);
            $this->em->flush();
            return true;
        }
    }


    private function removeFed($attr_req)
    {
        if (!empty($attr_req)) {
            $this->em->remove($attr_req);
            $this->em->flush();
            return true;
        }
    }

    public function fedsubmit()
    {
        log_message('debug', __METHOD__ . "fed-submited");
        $attr = $this->input->post('attribute');
        $status = $this->input->post('requirement');
        $reason = $this->input->post('reason');
        $action = $this->input->post('submit');
        $fedid = $this->input->post('fedid');
        if (empty($fedid) || !is_numeric($fedid) || empty($action) || empty($status) || empty($attr)) {
            show_error('Missing information in post', 403);
        }
        $f = $this->em->getRepository("models\Federation")->findOneBy(array('id' => '' . $fedid . ''));
        if (empty($f)) {
            show_error(lang('error_fednotfound', 404));
        }
        try {
            $hasWriteAccess = $this->zacl->check_acl('f_' . $f->getId() . '', 'write', 'federation', '');
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            show_error('Internal Server Error', 500);
        }

        if (!$hasWriteAccess) {
            $data = array('content_view' => 'nopermission', 'error' => '' . lang('rr_noperm_mngtattrforfed') . ': ' . $f->getName() . '');
            return $this->load->view(MY_Controller::$page, $data);
        }
        if ($attr && $status && in_array($action, array('Add', 'Modify', 'Remove'))) {
            if ($action === 'Add') {
                $isAttrReqExist = $this->em->getRepository("models\AttributeRequirement")->findBy(array('fed_id' => $fedid, 'attribute_id' => $attr));
                if (count($isAttrReqExist) == 0) {
                    $attribute = $this->em->getRepository("models\Attribute")->findOneBy(array('id' => $attr));
                    $attr_req = new models\AttributeRequirement;
                    $attr_req->setReason($reason);
                    $attr_req->setStatus($status);
                    $attr_req->setAttribute($attribute);
                    $attr_req->setType('FED');
                    $this->addFed($fedid, $attr_req);
                }
            } elseif ($action === 'Modify') {
                $attr_req = $this->em->getRepository("models\AttributeRequirement")->findOneBy(array('fed_id' => $fedid, 'attribute_id' => $attr));
                $attr_req->setReason($reason);
                $attr_req->setStatus($status);
                $attr_req->setType('FED');
                $this->em->persist($attr_req);
                $this->em->flush();
            } else {
                /** remove action */
                $attr_req = $this->em->getRepository("models\AttributeRequirement")->findBy(array('fed_id' => $fedid, 'attribute_id' => $attr));
                foreach ($attr_req as $v) {
                    $this->removeFed($v);
                }
            }
        }
        return $this->fed($fedid);
    }

}
