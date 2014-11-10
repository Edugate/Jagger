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
        
    }

    public function retrieveattrpath($idpid=null)
    {
        if(!$this->input->is_ajax_request() || (empty($idpid) || !is_numeric($idpid)))
        {
            show_error('method not allowed',403);
        }
        $arpByInherit = $this->config->item('arpbyinherit');
        if(!is_null($arpByInherit) || $arpByInherit === FALSE)
        {
           set_status_header(403);
           echo 'functionality disabled';
           return;
        }
        $this->load->library('zacl');
        $tmp_arps = new models\AttributeReleasePolicies;
        $langdrop = array('0'=>lang('dropnever'),'1'=> lang('dropokreq'),'2'=>lang('dropokreqdes'),'100'=>lang('dropnotset'));

        
 
        $idp = $this->em->getRepository("models\Provider")->findOneBy(array('id'=>$idpid,'type'=>array('IDP','BOTH')));
        if(empty($idp))
        {
          set_status_header(404);
          echo 'idp not found';
          return;
        }
        $has_write_access = $this->zacl->check_acl($idpid, 'write', 'entity', '');
        if(!$has_write_access)
        {
          set_status_header(403);
          echo 'no permission';
          return;
        }

        $requester = $this->input->post('requester');
        $attrname = $this->input->post('attribute');
        
        if(empty($requester) || empty($attrname))
        {
           set_status_header(403);
           echo 'missing params';
           return;
        }

        $attribute = $this->em->getRepository("models\Attribute")->findOneBy(array('name'=>$attrname));
        if(empty($attribute))
        {
           set_status_header(403);
           echo 'missing attr';
           return;
        }
        $sp = $this->em->getRepository("models\Provider")->findOneBy(array('entityid'=>$requester,'type'=>array('SP','BOTH')));
        if(empty($sp))
        {
           set_status_header(403);
           echo 'missing sp';
           return;
        }
        $result = array('status'=>'ok','requester'=>$requester,'attributename'=>$attrname);
        $result['details'] = array();
        $supportedAttr = $this->em->getRepository("models\AttributeReleasePolicy")->findOneBy(array('attribute'=>$attribute,'idp'=>$idp,'type'=>'supported'));
        $supported = false;
        if(!empty($supportedAttr))
        {
            $result['supported'] = true;
            $result['details'][] = array('name'=>'','value'=>lang('rr_supported'));
            $supported = true;
        }           
        else
        {
            $result['supported'] = false;
            $result['details'][] = array('name'=>'','value'=>lang('attrnotsupported'));
        }
        $globalPolicy =  $this->em->getRepository("models\AttributeReleasePolicy")->findOneBy(array('attribute'=>$attribute,'idp'=>$idp,'type'=>'global'));
        $global = 0;
        if(empty($globalPolicy))
        {
            $result['global'] = null;
            $val = ''.$langdrop['100'].' => deny';
            $result['details'][] = array('name'=>lang('rr_defaultarp'),'value'=>$val);
        }
        else
        {
            $result['global'] = $globalPolicy->getPolicy();
            $result['details'][] = array('name'=>lang('rr_default'),'value'=>$langdrop[$globalPolicy->getPolicy()]);
        }
        $idpfeds = $idp->getFederations();
        $spfeds = $sp->getFederations();
        $attrfed = null;
        $fedsmerged = array();
        foreach($spfeds as $s)
        {
            if($idpfeds->contains($s))
            {
                $tmpattrfed = $tmp_arps->getOneFedPolicyAttribute($idp,$s,$attribute->getId());
                if(!empty($tmpattrfed))
                {
                    $tmpattrfedPolicy = $tmpattrfed->getPolicy();
                    if($tmpattrfedPolicy !== null && $tmpattrfedPolicy >= $attrfed)
                    {
                        $attrfed = $tmpattrfedPolicy;
                        $fedsmerged[] = $sp->getName();
                    }
                }
            }
        }
        if($attrfed === null)
        {
            $result['details'][] = array('name'=>'federation','value'=>$langdrop['100'] .' => '.lang('rr_inheritfromparent'));
        }
        else
        {
            $fedsuffix = '';
            if(count($fedsmerged)>1)
            {
               $fedsuffix = '<br />'.lang('rr_merged').':<br />';
               $fedsuffix .= implode('<br />',$fedsmerged);
            }
            $result['details'][] = array('name'=>lang('rr_federation'),'value'=>$langdrop[''.$attrfed.''].$fedsuffix);
        }
        
        $specificPolicy = $tmp_arps->getOneSPPolicy($idp->getId(), $attribute->getId(), $sp->getId());
        $customPolicy = $tmp_arps->getOneSPCustomPolicy($idp->getId(), $attribute->getId(), $sp->getId());  
        if(empty($specificPolicy))
        {
            $result['details'][] = array('name'=>lang('rr_requester'),'value'=>$langdrop['100'] .' => '.lang('rr_inheritfromparent'));
        }
        else
        {
            $result['details'][] = array('name'=>lang('rr_requester'),'value'=>$langdrop[$specificPolicy->getPolicy()] );
        }
        if(!empty($customPolicy))
        {
            $rawdata = $customPolicy->getRawdata();
            if(is_array($rawdata))
            {
                $suffix = '';
                if(isset($rawdata['permit']) && is_array($rawdata['permit']))
                {
                    $suffix = '<br />'.lang('rr_permvalues').':<br />';
                    $suffix .= implode('<br />',$rawdata['permit']);
                }
                elseif(isset($rawdata['deny']) && is_array($rawdata['deny']))
                {
                    $suffix = '<br />'.lang('rr_denvalues').':<br />';
                    $suffix .= implode('<br />',$rawdata['deny']);

                }
                $result['details'][] = array('name'=>lang('custompolicy'),'value'=>'<small>'.lang('customappliedifpermited').'</small>'.$suffix);
            }
        }
        $this->output->set_content_type('application/json');
        echo json_encode($result);
   


    }
    public function submit_sp($idp_id) {

        if(!$this->input->is_ajax_request())
        {
            show_error('method not allowed',403);
        }
        $arpByInherit= $this->config->item('arpbyinherit');
        if(!is_null($arpByInherit) || $arpByInherit === FALSE)
        {
           set_status_header(403);
           echo 'functionality disabled';
           return;
        }
        if(!is_numeric($idp_id))
        {
           set_status_header(404);
           echo 'incorrect proivider id given';
           return;
        }
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            set_status_header(403);
            echo 'lost session';
            return;
        }
        $idp = $this->em->getRepository("models\Provider")->findOneBy(array('id'=>$idp_id,'type'=>array('IDP','BOTH')));
        if(empty($idp))
        {
            set_status_header(404);
            echo lang('rerror_providernotexist');
            return; 
        }
        $this->load->library('zacl');
        $has_write_access = $this->zacl->check_acl($idp_id, 'write', 'entity', '');
        if(!$has_write_access)
        {
            set_status_header(403);
            echo 'no permission';
            return;
        }
        $locked = $idp->getLocked();
        if($locked)
        {
            set_status_header(403);
            echo lang('rr_lockedentity');
            return;
        }
        $tmp_a = $this->config->item('policy_dropdown');
        $idpid = $this->input->post('idpid');
        if (empty($idpid) || !is_numeric($idpid)) {
            set_status_header(403);
            log_message('warning',  'idpid in post not provided or not numeric');
            echo lang('missedinfoinpost');
            return ;
        }
        if ($idp_id != $idpid) {
            log_message('error',  'idp id from post is not equal with idp in url, idp in post:' . $idpid . ', idp in url:' . $idp_id);
            set_status_header(403);
            echo lang('unknownerror');
            return;
        }
        $policy = trim($this->input->post('policy'));
        if (!isset($policy) || !is_numeric($policy)) {
            log_message('error',  'policy in post not provided or not numeric:' . $policy);
            set_status_header(403);
            echo lang('wrongpolicyval');
            return;
        }

        $requester = trim($this->input->post('requester'));
        $sp = $this->em->getRepository("models\Provider")->findOneBy(array('entityid'=>$requester,'type'=>array('SP','BOTH')));
        if(empty($sp))
        {
           set_status_header(403);
           echo 'Requester not found';
           return;
        }
        $attributename = trim($this->input->post('attribute'));
        $attribute = $this->em->getRepository("models\Attribute")->findOneBy(array('name'=>$attributename));
        if(empty($attribute))
        {
           set_status_header(403);
           echo 'Attribute not found';
           return;
        }

        if (!($policy == 0 || $policy == 1 || $policy == 2 )) {
            log_message('error', 'wrong policy in post: ' . $policy);
            set_status_header(403);
            echo lang('wrongpolicyval');
            return;
        }
        
        $changes = array();
        $tmp_arps = new models\AttributeReleasePolicies;
        $arp = $tmp_arps->getOneSPPolicy($idp_id, $attribute->getId(), $sp->getId());
        $customsp = $tmp_arps->getOneSPCustomPolicy($idp_id, $attribute->getId(), $sp->getId());
        if(empty($customsp))
        {
           $custom = false;
        }
        else
        {
           $custom = true;
        }
        if (!empty($arp)) {
                $old_policy = $arp->getPolicy();
                $arp->setPolicy($policy);
                $this->em->persist($arp);
                if($policy != $old_policy)
                {
                   $changes['attr: '.$attribute->getName().''] = array(
                      'before' => 'policy for '.htmlentities($sp->getEntityId()).' : '.$tmp_a[$old_policy] .'',
                      'after' => $tmp_a[$policy] ,
                   );
                   $this->tracker->save_track('idp', 'modification', $idp->getEntityId(), serialize($changes), false);
                }
                $this->em->flush();
                log_message('debug',  'action: modify - modifying arp from policy ' . $old_policy . ' to ' . $policy);
            } 
         else {
            log_message('debug', 'Arp not found');
                   log_message('debug',  'Creating new arp');
                $narp = new models\AttributeReleasePolicy;
                $narp->setSpecificPolicy($idp, $attribute, $sp->getId(), $policy);
                $this->em->persist($narp);
                $changes['attr: '.$attribute->getName().''] = array(
                      'before' => 'policy for '.htmlentities($sp->getEntityId()).' : not set/inherited',
                      'after' => $tmp_a[$policy] ,
                );
                $this->tracker->save_track('idp', 'modification', $idp->getEntityId(), serialize($changes), false);
                $this->em->flush();
        }
        $keyPrefix = getCachePrefix();
        $this->load->driver('cache', array('adapter' => 'memcached', 'key_prefix' => $keyPrefix));
        $cache2 = 'arp2_'.$idp_id;
        $this->cache->delete($cache2);
        $this->j_cache->library('arp_generator', 'arpToArrayByInherit', array($idp_id), -1);
        if($custom)
        {
          echo $policy.'c';
        }
        else
        {
          echo $policy;
        }
        return;


    }

}
