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

class Attribute_policyajax extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin) {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        }
    }

    public function submit_sp($idp_id) {

        if(!$this->input->is_ajax_request())
        {
            show_error('method not allowed',403);
        }
        $enabled = $this->config->item('arpbyinherit');
        if(empty($enabled) or $enabled !== TRUE)
        {
           show_error('disabled',403);
        }
        if(!is_numeric($idp_id))
        {
           show_error('incorrect id provided',404);
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            show_error('not authenticated', 403);
        }
        $idp = $this->em->getRepository("models\Provider")->findOneBy(array('id'=>$idp_id,'type'=>array('IDP','BOTH')));
        if(empty($idp))
        {
            show_error(lang('rerror_providernotexist'),404);
        }
        $this->load->library('zacl');
        $has_write_access = $this->zacl->check_acl($idp_id, 'write', 'entity', '');
        if(!$has_write_access)
        {
            show_error('no permission', 403);
        }
        $locked = $idp->getLocked();
        if($locked)
        {
            show_error(lang('rr_lockedentity'),403);
        }
        $idpid = $this->input->post('idpid');
        if (empty($idpid) or !is_numeric($idpid)) {
            log_message('error',  'idpid in post not provided or not numeric');
            show_error( lang('missedinfoinpost'), 404);
        }
        if ($idp_id != $idpid) {
            log_message('error',  'idp id from post is not equal with idp in url, idp in post:' . $idpid . ', idp in url:' . $idp_id);
            show_error( lang('unknownerror'), 404);
        }
        $policy = $this->input->post('policy');
        if (!isset($policy) or !is_numeric($policy)) {
            log_message('error',  'policy in post not provided or not numeric:' . $policy);
            show_error( lang('missedinfoinpost'), 404);
        }

        $requester = trim($this->input->post('requester'));
        $sp = $this->em->getRepository("models\Provider")->findOneBy(array('entityid'=>$requester,'type'=>array('SP','BOTH')));
        if(empty($sp))
        {
           show_error('sp not found',404);
        }
        $attributename = trim($this->input->post('attribute'));
        $attribute = $this->em->getRepository("models\Attribute")->findOneBy(array('name'=>$attributename));
        if(empty($attribute))
        {
           show_error('attr not found',404);
        }

        if (!($policy == 0 or $policy == 1 or $policy == 2 )) {
            log_message('error', 'wrong policy in post: ' . $policy);
            show_error( lang('wrongpolicyval'), 404);
        }
        
        $tmp_arps = new models\AttributeReleasePolicies;
        $arp = $tmp_arps->getOneSPPolicy($idp_id, $attribute->getId(), $sp->getId());
        if (!empty($arp)) {
                $old_policy = $arp->getPolicy();
                $arp->setPolicy($policy);
                $this->em->persist($arp);
                $this->em->flush();
                log_message('debug',  'action: modify - modifying arp from policy ' . $old_policy . ' to ' . $policy);
            } 
         else {
            log_message('debug', 'Arp not found');
                   log_message('debug',  'Creating new arp');
                $narp = new models\AttributeReleasePolicy;
                $narp->setSpecificPolicy($idp, $attribute, $sp->getId(), $policy);
                $this->em->persist($narp);
                $this->em->flush();
        }
        $keyPrefix = getCachePrefix();
        $this->load->driver('cache', array('adapter' => 'memcached', 'key_prefix' => $keyPrefix));
        $cache2 = 'arp2_'.$idp_id;
        $this->cache->delete($cache2);
        $this->j_cache->library('arp_generator', 'arpToArrayByInherit', array($idp_id), -1);

        echo $policy;
        return;


    }

}
