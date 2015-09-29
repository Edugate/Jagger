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
 * Attribute_policyajax Class
 *
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Attribute_policyajax extends MY_Controller
{

    protected $tmpArps;

    public function __construct()
    {
        parent::__construct();
        $this->tmpArps = new models\AttributeReleasePolicies;

    }

    public function getglobalattrpolicy($idpid, $attrid)
    {
        $isValidRequest = (ctype_digit($idpid) && ctype_digit($attrid) && $this->input->is_ajax_request() && $this->jauth->isLoggedIn());
        if (!$isValidRequest) {
            set_status_header(403);
            echo 'Access denied';
            return;
        }
        /**
         * @var $policy models\AttributeReleasePolicy
         */
        $policy = $this->em->getRepository("models\AttributeReleasePolicy")->findOneBy(array('attribute' => $attrid, 'idp' => $idpid, 'type' => 'global'));
        if (empty($policy)) {
            set_status_header(403);
            echo 'no found';
            return;
        }
        echo $policy->getPolicy();
    }

    public function getfedattrpolicy($idpid, $fedid, $attrid)
    {
        $isValidRequest = (ctype_digit($idpid) && ctype_digit($attrid) && ctype_digit($fedid) && $this->input->is_ajax_request() && $this->jauth->isLoggedIn());
        if (!$isValidRequest) {
            set_status_header(403);
            echo 'Access denied';
            return;
        }
        /**
         * @var $policy models\AttributeReleasePolicy
         */
        $policy = $this->em->getRepository("models\AttributeReleasePolicy")->findOneBy(array('attribute' => $attrid, 'idp' => $idpid, 'requester' => $fedid, 'type' => 'fed'));
        if (empty($policy)) {
            $resultPolicy = 100;
        } else {
            $resultPolicy = $policy->getPolicy();
        }

        $result = array(
            'attrid' => (int)$attrid,
            'idpid' => (int)$idpid,
            'type' => 'fed',
            'requester' => (int)$fedid,
            'status' => true,
            'policy' => $resultPolicy,
        );
        $this->output->set_content_type('application/json')->set_output(json_encode($result));


    }

    public function getattrpath($idpid, $spid, $attrid)
    {
        $isValidRequest = (ctype_digit($idpid) && ctype_digit($spid) && ctype_digit($attrid) && $this->input->is_ajax_request() && $this->jauth->isLoggedIn());
        if (!$isValidRequest) {
            set_status_header(403);
            echo 'Access denied';
            return;
        }
        $idp = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $idpid, 'type' => array('IDP', 'BOTH')));
        if (empty($idp)) {
            set_status_header(404);
            echo 'idp not found';
            return;
        }
        if (!$this->checkAccess($idp)) {
            set_status_header(403);
            echo 'Access denied';
            return;
        }

        $dropdownInLang = array('0' => lang('dropnever'), '1' => lang('dropokreq'), '2' => lang('dropokreqdes'), '100' => lang('dropnotset'));


        $attribute = $this->em->getRepository("models\Attribute")->findOneBy(array('id' => $attrid));
        if (empty($attribute)) {
            set_status_header(403);
            echo 'missing attr';
            return;
        }
        $sp = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $spid, 'type' => array('SP', 'BOTH')));
        if (empty($sp)) {
            set_status_header(403);
            echo 'missing sp';
            return;
        }
        $result = array('status' => 'ok', 'requester' => $sp->getEntityId(), 'attributename' => $attribute->getName(), 'details' => array());
        $supportedAttr = $this->em->getRepository("models\AttributeReleasePolicy")->findOneBy(array('attribute' => $attribute, 'idp' => $idp, 'type' => 'supported'));
        if (!empty($supportedAttr)) {
            $result['supported'] = true;
            $result['details'][] = array('name' => '', 'value' => lang('rr_supported'));
        } else {
            $result['supported'] = false;
            $result['details'][] = array('name' => '', 'value' => lang('attrnotsupported'));
        }
        $globalPolicy = $this->em->getRepository("models\AttributeReleasePolicy")->findOneBy(array('attribute' => $attribute, 'idp' => $idp, 'type' => 'global'));
        if (empty($globalPolicy)) {
            $result['global'] = null;
            $val = '' . $dropdownInLang['100'] . ' => deny';
            $result['details'][] = array('name' => lang('rr_defaultarp'), 'value' => $val);
        } else {
            $result['global'] = $globalPolicy->getPolicy();
            $result['details'][] = array('name' => lang('rr_default'), 'value' => $dropdownInLang[$globalPolicy->getPolicy()]);
        }
        $idpfeds = $idp->getFederations();
        $spfeds = $sp->getFederations();
        $attrfed = null;
        $fedsmerged = array();
        foreach ($spfeds as $s) {
            if ($idpfeds->contains($s)) {
                $tmpattrfed = $this->tmpArps->getOneFedPolicyAttribute($idp, $s, $attribute->getId());
                if (!empty($tmpattrfed)) {
                    $tmpattrfedPolicy = $tmpattrfed->getPolicy();
                    if ($tmpattrfedPolicy !== null && $tmpattrfedPolicy >= $attrfed) {
                        $attrfed = $tmpattrfedPolicy;
                        $fedsmerged[] = $sp->getName();
                    }
                }
            }
        }
        if ($attrfed === null) {
            $result['details'][] = array('name' => 'federation', 'value' => $dropdownInLang['100'] . ' => ' . lang('rr_inheritfromparent'));
        } else {
            $fedsuffix = '';
            if (count($fedsmerged) > 1) {
                $fedsuffix = '<br />' . lang('rr_merged') . ':<br />';
                $fedsuffix .= implode('<br />', $fedsmerged);
            }
            $result['details'][] = array('name' => lang('rr_federation'), 'value' => $dropdownInLang['' . $attrfed . ''] . $fedsuffix);
        }

        $specificPolicy = $this->tmpArps->getOneSPPolicy($idp->getId(), $attribute->getId(), $sp->getId());
        $customPolicy = $this->tmpArps->getOneSPCustomPolicy($idp->getId(), $attribute->getId(), $sp->getId());
        if (empty($specificPolicy)) {
            $result['details'][] = array('name' => lang('rr_requester'), 'value' => $dropdownInLang['100'] . ' => ' . lang('rr_inheritfromparent'));
        } else {
            $result['details'][] = array('name' => lang('rr_requester'), 'value' => $dropdownInLang[$specificPolicy->getPolicy()]);
        }
        if (!empty($customPolicy)) {
            $rawdata = $customPolicy->getRawdata();
            if (is_array($rawdata)) {
                $suffix = '';
                if (isset($rawdata['permit']) && is_array($rawdata['permit'])) {
                    $suffix = '<br />' . lang('rr_permvalues') . ':<br />';
                    $suffix .= implode('<br />', $rawdata['permit']);
                } elseif (isset($rawdata['deny']) && is_array($rawdata['deny'])) {
                    $suffix = '<br />' . lang('rr_denvalues') . ':<br />';
                    $suffix .= implode('<br />', $rawdata['deny']);

                }
                $result['details'][] = array('name' => lang('custompolicy'), 'value' => '<small>' . lang('customappliedifpermited') . '</small>' . $suffix);
            }
        }
        $this->output->set_content_type('application/json');
        echo json_encode($result);


    }

    /**
     * @param \models\Provider $provider
     * @return bool
     */
    private function checkAccess(models\Provider $provider)
    {
        $this->load->library('zacl');
        $isLocked = $provider->getLocked();
        $hasWriteAccess = $this->zacl->check_acl($provider->getId(), 'write', 'entity', '');
        return ($hasWriteAccess && !$isLocked);
    }

    public function submit_sp($idpID)
    {

        if (!ctype_digit($idpID) || !$this->input->is_ajax_request() || !$this->jauth->isLoggedIn()) {
            set_status_header(403);
            echo 'Access denied';
            return;
        }
        $idp = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $idpID, 'type' => array('IDP', 'BOTH')));
        if (empty($idp)) {
            set_status_header(404);
            echo lang('rerror_providernotexist');
            return;
        }
        if (!$this->checkAccess($idp)) {
            set_status_header(403);
            echo 'Access denied';
            return;
        }
        $this->load->library('zacl');
        $tmp_a = $this->config->item('policy_dropdown');
        $idpid = $this->input->post('idpid');
        if (empty($idpid) || !ctype_digit($idpid)) {
            set_status_header(403);
            log_message('warning', 'idpid in post not provided or not numeric');
            echo lang('missedinfoinpost');
            return;
        }
        if ($idpID != $idpid) {
            log_message('error', 'idp id from post is not equal with idp in url, idp in post:' . $idpid . ', idp in url:' . $idpID);
            set_status_header(403);
            echo lang('unknownerror');
            return;
        }
        $policy = trim($this->input->post('policy'));
        if (!isset($policy) || !is_numeric($policy)) {
            log_message('error', 'policy in post not provided or not numeric:' . $policy);
            set_status_header(403);
            echo lang('wrongpolicyval');
            return;
        }

        $requester = trim($this->input->post('requester'));
        $sp = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $requester, 'type' => array('SP', 'BOTH')));
        if (empty($sp)) {
            set_status_header(403);
            echo 'Requester not found';
            return;
        }
        $attributename = trim($this->input->post('attribute'));
        $attribute = $this->em->getRepository("models\Attribute")->findOneBy(array('name' => $attributename));
        if (empty($attribute)) {
            set_status_header(403);
            echo 'Attribute not found';
            return;
        }

        if (!in_array($policy, array('1', '2', '0', '100'))) {
            log_message('error', 'wrong policy in post: ' . $policy);
            set_status_header(403);
            echo lang('wrongpolicyval');
            return;
        }

        $changes = array();
        $tmp_arps = new models\AttributeReleasePolicies;
        $arp = $tmp_arps->getOneSPPolicy($idpID, $attribute->getId(), $sp->getId());
        $customsp = $tmp_arps->getOneSPCustomPolicy($idpID, $attribute->getId(), $sp->getId());
        $custom = true;
        if (empty($customsp)) {
            $custom = false;
        }
        if (!empty($arp)) {
            $old_policy = $arp->getPolicy();
            if ($policy === '100') {
                $this->em->remove($arp);
                $changes['attr: ' . $attribute->getName() . ''] = array(
                    'before' => 'policy for ' . $sp->getEntityId() . ' : ' . $tmp_a[$old_policy] . '',
                    'after' => 'removed',
                );
                $this->tracker->save_track('idp', 'modification', $idp->getEntityId(), serialize($changes), false);
            } elseif ($policy !== $old_policy) {
                $arp->setPolicy($policy);
                $this->em->persist($arp);

                $changes['attr: ' . $attribute->getName() . ''] = array(
                    'before' => 'policy for ' . $sp->getEntityId() . ' : ' . $tmp_a[$old_policy] . '',
                    'after' => $tmp_a[$policy],
                );
                $this->tracker->save_track('idp', 'modification', $idp->getEntityId(), serialize($changes), false);

            }

            log_message('debug', 'action: modify - modifying arp from policy ' . $old_policy . ' to ' . $policy);
        } else {
            if($policy !== '100') {
                $narp = new models\AttributeReleasePolicy;
                $narp->setSpecificPolicy($idp, $attribute, $sp->getId(), $policy);
                $this->em->persist($narp);
                $changes['attr: ' . $attribute->getName() . ''] = array(
                    'before' => 'policy for ' . $sp->getEntityId() . ' : not set/inherited',
                    'after' => $tmp_a[$policy]
                );
                $this->tracker->save_track('idp', 'modification', $idp->getEntityId(), serialize($changes), false);
            }
        }
        $this->em->flush();
        $keyPrefix = getCachePrefix();
        $this->load->driver('cache', array('adapter' => 'memcached', 'key_prefix' => $keyPrefix));
        $cache2 = 'arp_' . $idpID;
        $this->cache->delete($cache2);
        $this->j_cache->library('arp_generator', 'arpToArrayByInherit', array($idpID), -1);
        if ($custom) {
            echo $policy . 'c';
        } else {
            echo $policy;
        }
        return;


    }

    public function updatefed($idpID)
    {
        if (!$this->input->is_ajax_request() || !$this->jauth->isLoggedIn()) {
            set_status_header(401);
            echo 'access denied';
            return;
        }
        $postIdpID = trim($this->input->post('idpid'));
        $postAttrID = trim($this->input->post('attribute'));
        $postPolicy = trim($this->input->post('policy'));
        $postFedID = trim($this->input->post('fedid'));
        $allowedPols = array(0, 1, 2, 100);
        if (!ctype_digit($postFedID) || !ctype_digit($postAttrID) || !ctype_digit($postIdpID) || !ctype_digit($postPolicy) || strcasecmp($postIdpID, $idpID) != 0 || !in_array($postPolicy, $allowedPols)) {
            set_status_header(403);
            echo 'access denied';
            return;
        }
        $idp = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $idpID));
        $attribute = $this->em->getRepository("models\Attribute")->findOneBy(array('id' => $postAttrID));
        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $postFedID));
        if (empty($idp) || empty($attribute) || empty($federation)) {
            set_status_header(404);
            echo 'Not found 2';
            return;
        }
        if (!$this->checkAccess($idp)) {
            set_status_header(403);
            echo 'access denied 2';
            return;
        }
        $dropdown = $this->config->item('policy_dropdown');
        $dropdown[100] = lang('dropnotset');
        $policy = $this->em->getRepository("models\AttributeReleasePolicy")->findOneBy(array('attribute' => $postAttrID, 'idp' => $idpID, 'type' => 'fed', 'requester' => $postFedID));
        if (strcmp($postPolicy, '100') == 0) {
            if (empty($policy)) {
                $statusPol = 100;
            } else {
                $changes['before'] = $dropdown['' . $policy->getPolicy() . ''];
                $changes['after'] = $dropdown[100];
                $this->em->remove($policy);
                $statusPol = 100;
            }

        } elseif (empty($policy)) {
            $npolicy = new models\AttributeReleasePolicy();
            $npolicy->setFedPolicy($idp, $attribute, $federation, $postPolicy);
            $this->em->persist($npolicy);
            $statusPol = $postPolicy;


        } else {
            $policy->setPolicy($postPolicy);
            $this->em->persist($policy);
            $statusPol = $postPolicy;
        }
        try {
            $this->em->flush();

            $policyStr = '';
            if (array_key_exists($statusPol, $dropdown)) {
                $policyStr = $dropdown[$statusPol];
            }
            $result = array('status' => 1, 'policy' => $statusPol, 'attrid' => $postAttrID, 'policystr' => $policyStr);
            $this->j_cache->library('arp_generator', 'arpToArrayByInherit', array($idpID), -1);
            return $this->output->set_content_type('application/json')->set_output(json_encode($result));

        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            set_status_header(500);
            echo 'internal server error';

        }

    }

    public function updatedefault($idpID)
    {
        if (!$this->input->is_ajax_request() || !$this->jauth->isLoggedIn()) {
            set_status_header(401);
            echo 'access denied';
            return;
        }
        $postIdpID = trim($this->input->post('idpid'));
        $postAttrID = trim($this->input->post('attribute'));
        $postPolicy = trim($this->input->post('policy'));
        $allowedPols = array(0, 1, 2, 100);
        if (!ctype_digit($postAttrID) || !ctype_digit($postIdpID) || !ctype_digit($postPolicy) || strcasecmp($postIdpID, $idpID) != 0 || !in_array($postPolicy, $allowedPols)) {
            set_status_header(403);
            echo 'access denied';
            return;
        }

        $idp = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $idpID));
        $attribute = $this->em->getRepository("models\Attribute")->findOneBy(array('id' => $postAttrID));
        if (empty($idp) || empty($attribute)) {
            set_status_header(404);
            echo 'Not found 2';
            return;
        }

        if (!$this->checkAccess($idp)) {
            set_status_header(403);
            echo 'access denied';
            return;
        }

        $changes = array();
        $statusPol = null;
        $dropdown = $this->config->item('policy_dropdown');
        $dropdown[100] = lang('dropnotset');
        /**
         * @var $globalPolicy models\AttributeReleasePolicy
         */
        $globalPolicy = $this->em->getRepository("models\AttributeReleasePolicy")->findOneBy(array('attribute' => $postAttrID, 'idp' => $idpID, 'type' => 'global'));
        if (strcmp($postPolicy, '100') == 0) {
            if (empty($globalPolicy)) {
                $statusPol = 100;
            } else {
                $changes['before'] = $dropdown['' . $globalPolicy->getPolicy() . ''];
                $changes['after'] = $dropdown[100];
                $this->em->remove($globalPolicy);
                $statusPol = 100;
            }

        } elseif (empty($globalPolicy)) {
            $npolicy = new models\AttributeReleasePolicy();
            $npolicy->setGlobalPolicy($idp, $attribute, $postPolicy);
            $this->em->persist($npolicy);
            $statusPol = $postPolicy;


        } else {
            $globalPolicy->setPolicy($postPolicy);
            $this->em->persist($globalPolicy);
            $statusPol = $postPolicy;
        }
        try {
            $this->em->flush();

            $policyStr = '';
            if (array_key_exists($statusPol, $dropdown)) {
                $policyStr = $dropdown[$statusPol];
            }
            $result = array('status' => 1, 'policy' => $statusPol, 'attrid' => $postAttrID, 'policystr' => $policyStr);
            $this->j_cache->library('arp_generator', 'arpToArrayByInherit', array($idpID), -1);
            return $this->output->set_content_type('application/json')->set_output(json_encode($result));

        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            set_status_header(500);
            echo 'internal server error';

        }

    }

}
