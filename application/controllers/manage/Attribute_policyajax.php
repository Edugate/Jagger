<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2014 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 *
 */
class Attribute_policyajax extends MY_Controller
{

    protected $tmpArps;

    public function __construct() {
        parent::__construct();
        $this->tmpArps = new models\AttributeReleasePolicies;

    }

    public function getglobalattrpolicy($idpid, $attrid) {
        $isValidRequest = (ctype_digit($idpid) && ctype_digit($attrid) && $this->input->is_ajax_request() && $this->jauth->isLoggedIn());
        if (!$isValidRequest) {
            return $this->output->set_status_header(403)->set_output('Access denied');
        }
        /**
         * @var $policy models\AttributeReleasePolicy
         */
        $policy = $this->em->getRepository("models\AttributeReleasePolicy")->findOneBy(array('attribute' => $attrid, 'idp' => $idpid, 'type' => 'global'));
        if (empty($policy)) {
            return $this->output->set_status_header(404)->set_output('Policy not found');
        }
        echo $policy->getPolicy();
    }

    public function getfedattrpolicy($idpid, $fedid, $attrid) {
        $isValidRequest = (ctype_digit($idpid) && ctype_digit($attrid) && ctype_digit($fedid) && $this->input->is_ajax_request() && $this->jauth->isLoggedIn());
        if (!$isValidRequest) {
            return $this->output->set_status_header(403)->set_output('Access denied');
        }
        /**
         * @var $policy models\AttributeReleasePolicy
         */
        $policy = $this->em->getRepository("models\AttributeReleasePolicy")->findOneBy(array('attribute' => $attrid, 'idp' => $idpid, 'requester' => $fedid, 'type' => 'fed'));
        $resultPolicy = 100;
        if ($policy !== null) {
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

    public function getattrpath($idpid, $spid, $attrid) {
        $isValidRequest = (ctype_digit($idpid) && ctype_digit($spid) && ctype_digit($attrid) && $this->input->is_ajax_request() && $this->jauth->isLoggedIn());
        if (!$isValidRequest) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }
        /**
         * @var models\Provider $idp
         */
        $idp = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $idpid, 'type' => array('IDP', 'BOTH')));
        if ($idp === null) {
            return $this->output->set_status_header(404)->set_output('Identity Provider not found');
        }
        if (!$this->checkAccess($idp)) {
            return $this->output->set_status_header(403)->set_output('Access Denied');
        }

        $dropdownInLang = array('0' => lang('dropnever'), '1' => lang('dropokreq'), '2' => lang('dropokreqdes'), '100' => lang('dropnotset'));

        /**
         * @var models\Attribute $attribute
         */
        $attribute = $this->em->getRepository("models\Attribute")->findOneBy(array('id' => $attrid));
        if ($attribute === null) {
            return $this->output->set_status_header(404)->set_output('Requested attribute definition not found');
        }
        /**
         * @var models\Provider $sp
         */
        $sp = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $spid, 'type' => array('SP', 'BOTH')));
        if ($sp === null) {
            return $this->output->set_status_header(404)->set_output('Requested Service Provider not found');
        }
        $result = array('status' => 'ok', 'requester' => $sp->getEntityId(), 'attributename' => $attribute->getName(), 'details' => array());
        /**
         * @var models\AttributeReleasePolicy[] $supportedAttr
         */
        $supportedAttr = $this->em->getRepository("models\AttributeReleasePolicy")->findOneBy(array('attribute' => $attribute, 'idp' => $idp, 'type' => 'supported'));
        if (!empty($supportedAttr)) {
            $result['supported'] = true;
            $result['details'][] = array('name' => '', 'value' => lang('rr_supported'));
        } else {
            $result['supported'] = false;
            $result['details'][] = array('name' => '', 'value' => lang('attrnotsupported'));
        }
        /**
         * @var models\AttributeReleasePolicy[] $globalPolicy
         */
        $globalPolicy = $this->em->getRepository("models\AttributeReleasePolicy")->findOneBy(array('attribute' => $attribute, 'idp' => $idp, 'type' => 'global'));
        if ($globalPolicy === null) {
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
        return $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

    /**
     * @param \models\Provider $provider
     * @return bool
     */
    private function checkAccess(models\Provider $provider) {
        $this->load->library('zacl');
        $isLocked = $provider->getLocked();
        $hasWriteAccess = $this->zacl->check_acl($provider->getId(), 'write', 'entity', '');
        return ($hasWriteAccess && !$isLocked);
    }

    public function submit_sp($idpID) {

        if (!ctype_digit($idpID) || !$this->input->is_ajax_request() || !$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(403)->set_output('Access denied');
        }
        /**
         * @var models\Provider $idp
         */
        $idp = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $idpID, 'type' => array('IDP', 'BOTH')));
        if ($idp === null) {
            return $this->output->set_status_header(404)->set_output(lang('rerror_providernotexist'));
        }
        if (!$this->checkAccess($idp)) {
            return $this->output->set_status_header(403)->set_output('Access denied');
        }
        $this->load->library('zacl');
        $tmpAt = $this->config->item('policy_dropdown');
        $idpid = $this->input->post('idpid');
        if (empty($idpid) || !ctype_digit($idpid)) {
            log_message('warning', 'idpid in post not provided or not numeric');
            return $this->output->set_status_header(403)->set_output(lang('missedinfoinpost'));
        }
        if ($idpID != $idpid) {
            log_message('error', 'idp id from post is not equal with idp in url, idp in post:' . $idpid . ', idp in url:' . $idpID);
            return $this->output->set_status_header(500)->set_output(lang('unknownerror'));
        }
        $policy = trim($this->input->post('policy'));
        if (!isset($policy) || !is_numeric($policy)) {
            log_message('error', 'policy in post not provided or not numeric:' . $policy);
            return $this->output->set_status_header(403)->set_output(lang('wrongpolicyval'));
        }

        $requester = trim($this->input->post('requester'));
        $sp = $this->em->getRepository("models\Provider")->findOneBy(array('entityid' => $requester, 'type' => array('SP', 'BOTH')));
        if ($sp === null) {
            return $this->output->set_status_header(403)->set_output('Requester not found');
        }
        $attributename = trim($this->input->post('attribute'));
        $attribute = $this->em->getRepository("models\Attribute")->findOneBy(array('name' => $attributename));
        if (empty($attribute)) {
            return $this->output->set_status_header(403)->set_output('Attribute not found');
        }

        if (!in_array($policy, array('1', '2', '0', '100'))) {
            log_message('error', 'wrong policy in post: ' . $policy);
            return $this->output->set_status_header(403)->set_output(lang('wrongpolicyval'));
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
                    'before' => 'policy for ' . $sp->getEntityId() . ' : ' . $tmpAt[$old_policy] . '',
                    'after' => 'removed',
                );
                $this->tracker->save_track('idp', 'modification', $idp->getEntityId(), serialize($changes), false);
            } elseif ($policy !== $old_policy) {
                $arp->setPolicy($policy);
                $this->em->persist($arp);

                $changes['attr: ' . $attribute->getName() . ''] = array(
                    'before' => 'policy for ' . $sp->getEntityId() . ' : ' . $tmpAt[$old_policy] . '',
                    'after' => $tmpAt[$policy],
                );
                $this->tracker->save_track('idp', 'modification', $idp->getEntityId(), serialize($changes), false);

            }

            log_message('debug', 'action: modify - modifying arp from policy ' . $old_policy . ' to ' . $policy);
        } else {
            if ($policy !== '100') {
                $narp = new models\AttributeReleasePolicy;
                $narp->setSpecificPolicy($idp, $attribute, $sp->getId(), $policy);
                $this->em->persist($narp);
                $changes['attr: ' . $attribute->getName() . ''] = array(
                    'before' => 'policy for ' . $sp->getEntityId() . ' : not set/inherited',
                    'after' => $tmpAt[$policy]
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

    }

    public function updatefed($idpID) {
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
            return $this->output->set_status_header(403)->set_output('Access denie');
        }
        /**
         * @var models\Provider $idp
         */
        $idp = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $idpID));
        /**
         * @var models\Attribute[] $attribute
         */
        $attribute = $this->em->getRepository("models\Attribute")->findOneBy(array('id' => $postAttrID));
        $federation = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $postFedID));
        if ($idp === null || $attribute === null || $federation === null) {
            return $this->output->set_status_header(404)->set_output('Not found');
        }
        if (!$this->checkAccess($idp)) {
            return $this->output->set_status_header(403)->set_output('Access denie');
        }
        $dropdown = $this->config->item('policy_dropdown');
        $dropdown[100] = lang('dropnotset');
        /**
         * @var models\AttributeReleasePolicy[] $policy
         */
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
        } catch (Exception $e) {
            log_message('error', __METHOD__ . ' ' . $e);
            return $this->output->set_status_header(500)->set_output('Internal server error');
        }

        $policyStr = '';
        if (array_key_exists($statusPol, $dropdown)) {
            $policyStr = $dropdown[$statusPol];
        }
        $result = array('status' => 1, 'policy' => $statusPol, 'attrid' => $postAttrID, 'policystr' => $policyStr);
        $this->j_cache->library('arp_generator', 'arpToArrayByInherit', array($idpID), -1);
        return $this->output->set_content_type('application/json')->set_output(json_encode($result));

    }

    public function updatedefault($idpID) {
        if (!$this->input->is_ajax_request() || !$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(403)->set_output('Access denied');
        }
        $postIdpID = trim($this->input->post('idpid'));
        $postAttrID = trim($this->input->post('attribute'));
        $postPolicy = trim($this->input->post('policy'));
        $allowedPols = array(0, 1, 2, 100);
        if (!ctype_digit($postAttrID) || !ctype_digit($postIdpID) || !ctype_digit($postPolicy) || strcasecmp($postIdpID, $idpID) != 0 || !in_array($postPolicy, $allowedPols)) {
            return $this->output->set_status_header(403)->set_output('Access denied');
        }

        /**
         * @var models\Provider $idp
         */
        $idp = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $idpID));
        $attribute = $this->em->getRepository("models\Attribute")->findOneBy(array('id' => $postAttrID));
        if (empty($idp) || empty($attribute)) {
            return $this->output->set_status_header(404)->set_output('Not found');
        }

        if (!$this->checkAccess($idp)) {
            return $this->output->set_status_header(403)->set_output('Access denied');
        }

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
            return $this->output->set_status_header(500)->set_output('Internal server error');
        }

    }

}
