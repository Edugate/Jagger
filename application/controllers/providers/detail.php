<?php

if (!defined('BASEPATH'))
    exit('Ni direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2013, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * Provider_detail Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Detail extends MY_Controller {

    private $current_idp;
    private $current_idp_name;
    private $logo_url;
    private $tmp_attributes;

    function __construct()
    {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin)
        {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'refresh');
        }
        $this->load->helper(array('url', 'cert', 'url_encoder'));
        $this->load->library('table');
        $this->load->library('geshilib');
        $this->load->library('zacl');
        $this->load->library('show_element');
        $this->logo_basepath = $this->config->item('rr_logouriprefix');
        $this->logo_baseurl = $this->config->item('rr_logobaseurl');
        if (empty($this->logo_baseurl))
        {
            $this->logo_baseurl = base_url();
        }
        $this->logo_url = $this->logo_baseurl . $this->logo_basepath;
        $this->tmp_attributes = new models\Attributes;
        $this->tmp_attributes->getAttributes();
    }
    function show($id)
    {
       if(empty($id) or !is_numeric($id)) 
       {
          show_error('Page not found',404);
          return;
       }
       $tmp_providers = new models\Providers();
       $ent = $tmp_providers->getOneById($id);
       if(empty($ent))
       {
          show_error('Entity not found', 404);
          return;
       }
       $is_static = $ent->getStatic();
       $params = array(
            'enable_classes' => true,
        );
        //echo '<pre>';
        //print_r($this->session->all_userdata());
        //echo '</pre>';
       $sppart = FALSE;
       $idppart = FALSE;
       $type = strtolower($ent->getType());
       $data['type'] = $type;
       $group = 'entity';
       $entstatus = '';
       
       if($type == 'both')
       {
          $sppart = TRUE;
          $idppart = TRUE;
          $data['presubtitle'] = lang('rr_asboth');
       }
       elseif($type == 'idp')
       {
          $idppart = TRUE;
          $data['presubtitle'] = lang('identityprovider');
       }
       elseif($type == 'sp')
       {
          $sppart = TRUE;
          $data['presubtitle'] = lang('serviceprovider');
       }
       $has_read_access = $this->zacl->check_acl($id, 'read', $group, '');
       $has_write_access = $this->zacl->check_acl($id, 'write', $group, '');
       $has_manage_access = $this->zacl->check_acl($id, 'manage', $group, '');
       if (!$has_read_access)
       {
           $data['content_view'] = 'nopermission';
           $data['error'] = lang("rr_nospaccess");
           $this->load->view('page', $data);
           return;
       }
       $is_validtime = $ent->getIsValidFromTo();
       $is_active = $ent->getActive();
       $locked = $ent->getLocked();
       $lockicon = genIcon('locked',lang('rr_locked'));
       $edit_link = '';
       if (empty($is_active))
       {
           $entstatus .= '<span class="lbl lbl-disabled">' . lang('rr_disabled') . '</span>';
       }
       else
       {
           $entstatus .= '<span class="lbl lbl-active">'.lang('rr_enabled').'</span> ';
       }
       if(!$is_validtime)
       {
            $entstatus .= '<span class="lbl lbl-alert" title="'.lang('rr_validfromto_notmatched1').'">metadata '.lang('rr_expired').'</span> ';   
       }
       if($locked)
       {
           $entstatus .= ' <span class="lbl lbl-locked" title="cannot be edited">'.lang('rr_locked').'</span> ';
       }
       if($ent->getLocal())
       {
          $entstatus .= ' <span class="lbl lbl-local">'.lang('rr_managedlocally').'</span> ';
       }
       else
       {
          $entstatus .= ' <span class="lbl lbl-local">'.lang('rr_external').'</span> ';
       }
       if($is_static)
       {
          $entstatus .= ' <span class="lbl lbl-static" title="static metadata is set on">'.lang('lbl_static').'</span> ';
          $edit_link .= ' <span class="lbl lbl-static" title="static metadata is set on">'.lang('lbl_static').'</span> ';
       }
       
       if (!$has_write_access)
       {
           $edit_link .= '<span class="lbl lbl-noperm" title="' . lang('rr_nopermission') . '">'.lang('rr_nopermission').'</span>';
       }
       elseif (!$ent->getLocal())
       {
           $edit_link .= '<span class="lbl lbl-external" title="' . lang('rr_externalentity') . '">'.lang('rr_external').'</span>';
       }
       elseif ($locked)
       {
           $edit_link .= '<span class="lbl lbl-locked" title="' . lang('rr_lockedentity') . '">'.lang('rr_lockedentity').'</span>';
       }
       else
       {
           $edit_link .= '<a href="' . base_url() . 'manage/entityedit/show/' . $id . '" class="edit" title="edit" ><span class="lbl lbl-edit">' . genIcon('edit') . lang('rr_edit').'</span></a>';
           
       }
        $data['edit_link'] =   $edit_link ;
       
       
       $extend=$ent->getExtendMetadata();
       /**
        * get first assinged logo to display on site 
        */
       $is_logo = false;
       foreach($extend as $v)
       {
          if($is_logo)
          {
             break;
          }
          if($v->getElement() == 'Logo')
          {
               $data['provider_logo_url'] = $v->getLogoValue();
               $is_logo = TRUE;
          }
       }
       
       
       $data['entid'] = $ent->getId();
       $data['name'] = $ent->getName();
       if(empty($data['name']))
       {
           $data['name'] = $ent->getEntityId();
       }
       $this->title = lang('rr_providerdetails') . ' :: ' . $data['name'] ;
       $b = $this->session->userdata('board');
       if(!empty($b) && is_array($b))
       {
          if(($type == 'idp' or $type == 'both') && isset($b['idp'][$id]))
          {
             $data['bookmarked'] = true;
          }
          elseif(($type == 'sp' or $type == 'both') && isset($b['sp'][$id]))
          {
             $data['bookmarked'] = true;
          }
       }
       

       /**
        * BASIC
        */
       $d = array();
       $i = 0;
       $d[++$i]['header'] = '<span id="basic"></span>' . lang('rr_basicinformation') ; 
       $d[++$i]['name'] = 'Status';

       $d[$i]['value'] = '<b>'.$entstatus.'</b>';
       $d[++$i]['name'] = lang('rr_lastmodification');
       $d[$i]['value'] =  '<b>'.$ent->getLastModified()->format('Y-m-d H:i:s').'</b>';
       $d[++$i]['name'] = lang('rr_providername');
       $d[$i]['value'] = $ent->getName();
       $d[++$i]['name'] = lang('rr_entityid');
       $d[$i]['value'] = $ent->getEntityId();
       $lname = $ent->getLocalName();
       $lvalues = '';
       if(is_array($lname))
       {
           $d[++$i]['name'] = lang('rr_providername') . ' <small>localized</small>';
           foreach($lname as $k=>$v)
           {
               $lvalues .= '<b>'.$k.':</b> '.$v.'<br />';
           }
           $d[$i]['value'] =  $lvalues ;
       }
       $d[++$i]['name'] = lang('rr_descriptivename');
       $d[$i]['value'] = '<div id="selectme">'.$ent->getDisplayName().'</div>';
       $ldisplayname = $ent->getLocalDisplayName();
       $lvalues = '';
       if(is_array($ldisplayname))
       {
          $d[++$i]['name'] = lang('rr_descriptivename') . ' <small>(localized)</small>';
          foreach($ldisplayname as $k=>$v)
          {
             $lvalues .= '<b>'.$k.':</b> '.$v.'<br />';
          }
          $d[$i]['value'] =  $lvalues ;
       }
       $d[++$i]['name'] = lang('rr_regauthority');
       $regauthority = $ent->getRegistrationAuthority();
       $confRegAuth = $this->config->item('registrationAutority');
       $confRegLoad = $this->config->item('load_registrationAutority');
       $confRegistrationPolicy = $this->config->item('registrationPolicy');
       $regauthoritytext = null;
       if (empty($regauthority))
       {
           if ($ent->getLocal() && !empty($confRegLoad) && !empty($confRegAuth))
           {
              $regauthoritytext = lang('rr_regauthority_alt') . ' <b>' . $confRegAuth . '</b>';
           }
           $d[$i]['value'] = $regauthoritytext .'<br /><small><i>loaded from global config</i></small>';
        }
        else
        {
           $d[$i]['value'] = $regauthority;
        }

        $d[++$i]['name'] = lang('rr_regdate');
        $regdate = $ent->getRegistrationDate();
        if (isset($regdate))
        {
            $d[$i]['value'] = $regdate->format('Y-m-d');
        }
        else
        {
            $d[$i]['value'] = null;
        }
        $regpolicy = $ent->getRegistrationPolicy();
        $regpolicy_value = '';
        if(count($regpolicy) > 0)
        {
            foreach($regpolicy as $rkey=>$rvalue)
            {
               $regpolicy_value .= '<b>'.$rkey.':</b> '.$rvalue.'<br />';
            }
        }
        elseif(!empty($confRegistrationPolicy) && !empty($confRegLoad))
        {
            $regpolicy_value .= '<b>en:</b> '.$confRegistrationPolicy.' <br /><small><i>loaded from global config</i></small>';
        }
        $d[++$i]['name'] = lang('rr_regpolicy');
        $d[$i]['value'] = $regpolicy_value;
        $d[++$i]['name'] = lang('rr_description');
        $d[$i]['value'] = $ent->getDescription();
        $ldescription = $ent->getLocalDescription();
        $lvalues = '';
        if(is_array($ldescription))
        {
           $d[++$i]['name'] = lang('rr_description') . ' <small>localized</small>';
           foreach($ldescription as $k=>$v)
           {
               $lvalues .= '<b>'.$k.':</b> <div>'.$v.'</div>';
           } 
           $d[$i]['value'] =  $lvalues ;
        }
        
        $d[++$i]['name'] = lang('rr_homeurl');
        $d[$i]['value'] = $ent->getHomeUrl() .' <br /><small>'.lang('rr_notincludedmetadata').'</small>';
        $d[++$i]['name'] = lang('rr_helpdeskurl');
        $d[$i]['value'] = $ent->getHelpdeskUrl() .' <br /><small>'.lang('rr_includedmetadata').'  &lt;md:OrganizationURL ..../&gt;</small>';
        $d[++$i]['name'] = lang('rr_defaultprivacyurl');
        $d[$i]['value'] = $ent->getPrivacyUrl();
        $d[++$i]['name'] = lang('rr_coc');
        $coc = $ent->getCoc();
        if(!empty($coc))
        {
           $cocvalue = $coc->getName().'<br />'.anchor($coc->getUrl());
           if(!$coc->getAvailable())
           {
              $cocvalue .=' <span class="lbl lbl-disabled">'.lang('rr_disabled').'</span>';
           }
        }
        else
        {
           $cocvalue = lang('rr_notset');
        }
        $d[$i]['value'] = $cocvalue;
       
        $d[++$i]['name'] = lang('rr_validfromto');
        if($ent->getValidFrom())
        {
           $validfrom = $ent->getValidFrom()->format('Y M d');
        }
        else
        {
           $validfrom = lang('rr_unlimited');
        }
        if($ent->getValidTo())
        {
           $validto = $ent->getValidTo()->format('Y M d');
        }
        else
        {
           $validto = lang('rr_unlimited');
        }
        if($is_validtime)
        {    
            $d[$i]['value'] = $validfrom . ' <b>--</b> '.$validto;
        }
        else
        {
            $d[$i]['value'] = '<span class="lbl lbl-alert">'.$validfrom . ' <b>--</b> '.$validto.'</span>';
        }
        $result[] = array('section'=>'general','title'=>'General','data'=>$d);   
        /**
         * Federation
         */
        $d = array();
        $i = 0;
        $d[++$i]['header'] = '<span id="federation"></span>' . lang('rr_federation');
        $d[++$i]['name'] = lang('rr_memberof');
        $federationsString = "";
        $all_federations = $this->em->getRepository("models\Federation")->findAll();
        $feds = $ent->getFederations();
        if (!empty($feds))
        {
            $federationsString = '<ul>';
            foreach ($feds->getValues() as $f)
            {
                $fedlink = base_url('federations/manage/show/' . base64url_encode($f->getName()));
                $metalink = base_url('metadata/federation/' . base64url_encode($f->getName()) . '/metadata.xml');
                $federationsString .= '<li>' . anchor($fedlink, $f->getName()) . ' <span class="accordionButton">'.lang('rr_metadataurl').':</span><span class="accordionContent"><br />' . $metalink . '&nbsp;</span> &nbsp;&nbsp;' . anchor_popup($metalink, '<img src="' . base_url() . 'images/icons/arrow.png"/>') . '</li>';
            }
            $federationsString .='</ul>';
            $manage_membership = '';
            $no_feds = $feds->count();
            if ($no_feds > 0 && $has_write_access)
            {
                if (!$locked)
                {
                   $manage_membership .= '<b>'.lang('rr_federationleave').'</b> ' . anchor(base_url() . 'manage/leavefed/leavefederation/' . $ent->getId(), '<img src="' . base_url() . 'images/icons/arrow.png"/>') . '<br />';
                }
                else
                {
                   $manage_membership .= '<b>'.lang('rr_federationleave').'</b> ' . $lockicon . ' <br />';
                }
            }
            if ($has_write_access && ($feds->count() < count($all_federations)))
            {
               if (!$locked)
               {
                   $manage_membership .= '<b>'.lang('rr_federationjoin').'</b> ' . anchor(base_url() . 'manage/joinfed/joinfederation/' . $ent->getId(), '<img src="' . base_url() . 'images/icons/arrow.png"/>') . '<br />';
               }
               else
               {
                           $manage_membership .= '<b>'.lang('rr_federationjoin').'</b> ' . $lockicon . '<br />';
                      }
                   }
               }
              $d[$i]['value'] = '<p>' . $federationsString . '</p>' . '<p>'.$manage_membership.'</p>';
              if($no_feds > 0)
              { 
                   $d[++$i]['name'] = '';
                   $d[$i]['value'] = '<a href="'.base_url().'providers/detail/showmembers/'.$id.'" id="getmembers"><button type="button" class="btn">Show members</button></a>';
                   
                   $d[++$i]['2cols'] = '<div id="membership"></div>';
              }
              $result[] = array('section'=>'federation','title'=>'Membership','data'=>$d);

              $d = array();
              $i = 0;
              if($sppart)
              {
                  $d[++$i]['header'] = 'WAYF';
                  $d[++$i]['name'] = lang('rr_ds_disco_url');
                  $d[$i]['value'] = anchor(base_url().'disco/circle/'.base64url_encode($ent->getEntityId()).'/metadata.json?callback=dj_md_1','Link');
                  
                  $tmpwayflist = $ent->getWayfList();
                  if(!empty($tmpwayflist) && is_array($tmpwayflist))
                  {
                     if(isset($tmpwayflist['white'])) 
                     {
                         if(is_array($tmpwayflist['white']))
                         {
                             $discolist = implode('<br />', array_values($tmpwayflist['white']));
                             $d[++$i]['name'] = lang('rr_ds_white');
                             $d[$i]['value'] = $discolist; 
                         }
                     }
                   elseif(isset($tmpwayflist['black']) && is_array($tmpwayflist['black']) && count($tmpwayflist['black'])>0 )
                   {
                        $discolist = implode('<br />', array_values($tmpwayflist['black']));
                        $d[++$i]['name'] = lang('rr_ds_black');
                        $d[$i]['value'] = $discolist; 
                   }
                 }

              }
      
       $d[++$i]['header'] = '<span id="technical"></span>' . lang('rr_technicalinformation');
       $d[++$i]['name'] = lang('rr_entityid');
       $d[$i]['value'] = $ent->getEntityId();
       if($idppart)
       {
          $d[++$i]['name'] = lang('rr_domainscope') . '<br /><i>IDPSSODescriptor</i>';
          $scopes = $ent->getScope('idpsso');
          $scopeString = '<ul>';
          foreach ($scopes as $key => $value)
          {
              $scopeString .= '<li>' . $value . '</li>';
          }
          $scopeString .= '</ul>';
          $d[$i]['value'] = $scopeString;
          $d[++$i]['name'] = lang('rr_domainscope') . '<br /><i>AttributeAuthorityDescriptor</i>';
          $scopes = $ent->getScope('aa');
          $scopeString = '<ul>';
          foreach ($scopes as $key => $value)
          {
              $scopeString .= '<li>' . $value . '</li>';
          }
          $scopeString .= '</ul>';
          $d[$i]['value'] = $scopeString;

       }
      
       $srv_metalink = base_url("metadata/service/" . base64url_encode($ent->getEntityId()) . "/metadata.xml");
       $srv_circle_metalink = base_url() . 'metadata/circle/' . base64url_encode($ent->getEntityId()) . '/metadata.xml';
       $srv_circle_metalink_signed = base_url() . 'signedmetadata/provider/' . base64url_encode($ent->getEntityId()) . '/metadata.xml'; 
       $d[++$i]['name'] = '<a name="metadata"></a>' . lang('rr_servicemetadataurl');
       $d[$i]['value'] = '<span class="accordionButton">' . lang('rr_metadataurl') . '</span><span class="accordionContent"><br />' . $srv_metalink . '&nbsp;</span>&nbsp; ' . anchor_popup($srv_metalink, '<img src="' . base_url() . 'images/icons/arrow.png"/>');

       $d[++$i]['name'] = lang('rr_circleoftrust');
       $d[$i]['value'] = '<span class="accordionButton">' . lang('rr_metadataurl') . '</span><span class="accordionContent"><br />' . $srv_circle_metalink . '&nbsp;</span>&nbsp; ' . anchor_popup($srv_circle_metalink, '<img src="' . base_url() . 'images/icons/arrow.png"/>');
       $d[++$i]['name'] = lang('rr_circleoftrust') . '(signed)';
       $d[$i]['value'] = '<span class="accordionButton">' . lang('rr_metadataurl') . '</span><span class="accordionContent"><br />' . $srv_circle_metalink_signed . '&nbsp;</span>&nbsp; ' . anchor_popup($srv_circle_metalink_signed, '<img src="' . base_url() . 'images/icons/arrow.png"/>');
      
        $result[] = array('section'=>'technical','title'=>'Technical','data'=>$d);

        $d = array();
        $i = 0;

        if ($is_static)
        {
            $tmp_st = $ent->getStaticMetadata();
            if (!empty($tmp_st))
            {
                $static_metadata = $tmp_st->getMetadata();
            }
            else
            {
                $static_metadata = null;
            }
            if (empty($static_metadata))
            {
                $d[++$i]['name'] = lang('rr_staticmetadataactive');
                $d[$i]['value'] = '<span class="alert">' . lang('rr_isempty') . '</span>';
            }
            else
            {
                $d[++$i]['header'] = lang('rr_staticmetadataactive');

                $d[++$i]['2cols'] = '<code>' . $this->geshilib->highlight($static_metadata, 'xml', $params) . '</code>';
            }
        $result[] = array('section'=>'staticmeta','title'=>'Static Metadata','data'=>$d);

        $d = array();
        $i = 0;
        }
        

        $d[++$i]['header'] = lang('rr_supportedprotocols');
        if($type != 'sp')
        {
            $d[++$i]['name'] = lang('rr_supportedprotocols') . ' <i>IDPSSODescriptor</i>';
            $v = implode('<br />',$ent->getProtocolSupport('idpsso'));
            $d[$i]['value'] = $v;
            $d[++$i]['name'] = lang('rr_supportedprotocols') . ' <i>AttributeAuthorityDescriptor</i>';
            $v = implode('<br />',$ent->getProtocolSupport('aa'));
            $d[$i]['value'] = $v;
        } 
        if($type != 'idp')
        {
            $d[++$i]['name'] = lang('rr_supportedprotocols') . ' <i>SPSSODescriptor</i>';
            $v = implode('<br />',$ent->getProtocolSupport('spsso'));
            $d[$i]['value'] = $v;
        } 
        
        if($type != 'sp')
        {
           $d[++$i]['name'] = lang('rr_supportednameids') . ' <i>IDPSSODescriptor</i>';
           $nameids = '';
           foreach ($ent->getNameIds('idpsso') as $r)
           {
               $nameids .= '<li>' . $r . '</li>';
           }
           $nameids .='</ul>';
           $d[$i]['value'] = trim($nameids);

           $aanameids = $ent->getNameIds('aa');
           $aanameid = '';
           if(count($aanameids) > 0)
           {
                $d[++$i]['name'] = lang('rr_supportednameids') . ' <i>AttributeAuthorityDescriptor</i>';
                foreach($aanameids as $r)
                {
                   $aanameid .= '<li>' . $r . '</li>';
                }
                 $aanameid .= '</ul>';
                $d[$i]['value'] = trim($aanameid);
           }
        }
        if($type != 'idp')
        {
           $nameids = '';
           $d[++$i]['name'] = lang('rr_supportednameids') . ' <i>SPSSODescriptor</i>';
           foreach ($ent->getNameIds('spsso') as $r)
           {
               $nameids .= '<li>' . $r . '</li>';
           }
           $nameids .='</ul>';
           $d[$i]['value'] = trim($nameids);

        }

      
       /**
        * ServiceLocations
        */
        $d[++$i]['header'] = lang('rr_servicelocations');
        $srvs = $ent->getServiceLocations();
        if($srvs->count() > 0)
        {
            foreach($srvs as $v)
            {
               $services[$v->getType()][] = $v;
            }
        }
       if($idppart)
       {
          if(array_key_exists('SingleSignOnService',$services))
          {
               $ssovalues = '';
               $d[++$i]['name'] = 'SingleSignOnService <br /><small>IDPSSODescriptor</small>';
               foreach($services['SingleSignOnService'] as $s)
               {
                  $def = "";
                  if ($s->getDefault())
                  {
                      $def = "<i>(default)</i>";
                  }
                  $ssovalues .= '<li><b>' . $def . ' ' . $s->getUrl() . '</b><br /><small>' . $s->getBindingName() . '</small></li>';
               }
               $d[$i]['value'] = '<ul>'.$ssovalues.'</ul>';
         
          }
          if(array_key_exists('IDPSingleLogoutService',$services))
          { 
             $d[++$i]['name'] = 'SingleLogoutService <br /><small>IDPSSODescriptor</small>';
             $slvalues = '';
             foreach($services['IDPSingleLogoutService'] as $s)
             {
                 $slvalues .=  '<b> '  . $s->getUrl() . '</b><br /><small>' . $s->getBindingName() . '</small><br />';
                
             }
             $d[$i]['value'] = $slvalues;
          } 
          if(array_key_exists('IDPArtifactResolutionService',$services))
          { 
             $d[++$i]['name'] = 'ArtifactResolutionService <br /><small>IDPSSODescriptor</small>';
             $slvalues = '';
             foreach($services['IDPArtifactResolutionService'] as $s)
             {
                 $slvalues .=  '<b>'  . $s->getUrl() . '</b> <small><i>index: '.$s->getOrder().'</i></small><br /><small>' . $s->getBindingName() . '</small><br />';
                
             }
             $d[$i]['value'] = $slvalues;
          } 
          if(array_key_exists('IDPAttributeService',$services))
          { 
             $d[++$i]['name'] = 'AttributeService <br /><small>AttributeAuthorityDescriptor</small>';
             $slvalues = '';
             foreach($services['IDPAttributeService'] as $s)
             {
                 $slvalues .=  '<b>'  . $s->getUrl() . '</b><br /><small>' . $s->getBindingName() . '</small><br />';
                
             }
             $d[$i]['value'] = $slvalues;
          } 
       }
       if($sppart)
       {
          if(array_key_exists('AssertionConsumerService',$services))
          {
              $acsvalues = '';
              $d[++$i]['name'] = 'AssertionConsumerService <br /><small>SPSSODescriptor</small>';
              foreach($services['AssertionConsumerService'] as $s)
              {
                $def = '';
                if ($s->getDefault())
                {
                    $def = '<i>('.lang('rr_default').')</i>';
                }
                $acsvalues .= '<li><b>' . $def . ' ' . $s->getUrl() . '</b> <small><i>index: '.$s->getOrder().'</i></small><br /><small>' . $s->getBindingName() . ' </small></li>';
              }
              $d[$i]['value'] = '<ul>'.$acsvalues.'</ul>';
          }
          if(array_key_exists('SPArtifactResolutionService',$services))
          {
              $acsvalues = '';
              $d[++$i]['name'] = 'ArtifactResolutionService <br /><small>SPSSODescriptor</small>';
              foreach($services['SPArtifactResolutionService'] as $s)
              {
                $def = '';
                if ($s->getDefault())
                {
                    $def = '<i>('.lang('rr_default').')</i>';
                }
                $acsvalues .= '<li><b>' . $def . ' ' . $s->getUrl() . '</b> <small><i>index: '.$s->getOrder().'</i></small><br /><small>' . $s->getBindingName() . ' </small></li>';
              }
              $d[$i]['value'] = '<ul>'.$acsvalues.'</ul>';
          }
          if(array_key_exists('SPSingleLogoutService',$services))
          { 
             $d[++$i]['name'] = 'SingleLogoutService <br /><small>SPSSODescriptor</small>';
             $slvalues = '';
             foreach($services['SPSingleLogoutService'] as $s)
             {
                 $slvalues .=  '<li><b> '  . $s->getUrl() . '</b><br /><small>' . $s->getBindingName() . '</small></li>';
                
             }
             $d[$i]['value'] = '<ul>'.$slvalues.'</ul>';
          } 
          if(array_key_exists('RequestInitiator',$services))
          {
             $d[++$i]['name'] = 'RequestInitiator <br /><small>SPSSODescriptor/Extensions</small>';
             $rivalues = '';
             foreach($services['RequestInitiator'] as $s)
             {
                 $rivalues .= '<li><b>' . $s->getUrl() . '</b><br /><small>' . $s->getBindingName() . '</small></li>';
             }
             $d[$i]['value'] = '<ul>'.$rivalues.'</ul>';
          }
          if(array_key_exists('DiscoveryResponse',$services))
          {
              $d[++$i]['name'] = 'DiscoveryResponse <br /><small>SPSSODescriptor/Extensions</small>';
              $drvalues = '';
              foreach($services['DiscoveryResponse'] as $s)
              { 
                 $drvalues .= '<li><b>' . $s->getUrl() . '</b>&nbsp;&nbsp;<small><i>index:'.$s->getOrder().'</i></small><br /><small>' . $s->getBindingName() . '</small></li>';
              }
               $d[$i]['value'] = '<ul>'.$drvalues.'</ul>';
          }

       }
       $result[] = array('section'=>'otherthec','title'=>'Other Tech','data'=>$d);
       $d=array();
       $i = 0;
       $tcerts = $ent->getCertificates(); 
       $certs = array();
       foreach($tcerts as $c)
       {
           $certs[$c->getType()][] = $c;
       }
       $d[++$i]['header'] = lang('rr_certificates');
       if($idppart)
       {
           $d[++$i]['name'] = 'Certificates in IDPSSODescriptor';
           $cString = '';
           if(array_key_exists('idpsso',$certs))
           {
               foreach($certs['idpsso'] as $v)
               {
                   $certusage = $v->getCertuse();
                   if(empty($certusage))
                   {
                      $cString .= '<i>signing/encryption</i><br />';
                   }
                   else
                   {
                      $cString .= '<i>'.$certusage.'</i><br />';
                   }
                   $kname = $v->getKeyname();
                   $c_certData = $v->getCertData();
                   if(!empty($kname))
                   {
                     $cString .='<b>'.lang('rr_keyname').':</b><br /> ' . str_replace(',','<br />',$kname) . '<br />';
                   }
                   if (!empty($c_certData))
                   {  
                       $c_certtype = $v->getCertType();
                       if ($c_certtype == 'X509Certificate')
                       {
                          $c_fingerprint = $v->getFingerprint();
                          $c_certValid = validateX509($c_certData);
                          if (!$c_certValid)
                          {
                             $cString .='<span class="error">' . lang('rr_certificatenotvalid') . '</span>';
                          }
                        }
                        if (!empty($c_fingerprint))
                        {
                           $cString .='<b>'.lang('rr_fingerprint').':</b> <span>' . $c_fingerprint . '</span><br />';
                        }
                        $cString .= '<span class="accordionButton"><b>'.lang('rr_certbody').'</b><br /></span><code class="accordionContent">' . trim($c_certData) . '</code>';
                        
                   
                    }
                       $cString .= '<br />';
               }
               $d[$i]['value'] = $cString;
           }
       // AA
           if(array_key_exists('aa',$certs))
           {
               $d[++$i]['name'] = 'Certificates in AttributeAuthorityDescriptor';
               $cString = '';
               foreach($certs['aa'] as $v)
               {
                   $certusage = $v->getCertuse();
                   if(empty($certusage))
                   {
                      $cString .= '<i>signing/encryption</i><br />';
                   }
                   else
                   {
                      $cString .= '<i>'.$certusage.'</i><br />';
                   }
                   $kname = $v->getKeyname();
                   $c_certData = $v->getCertData();
                   if(!empty($kname))
                   {
                     $cString .='<b>'.lang('rr_keyname').':</b><br /> ' . str_replace(',','<br />',$kname) . '<br />';
                   }
                   if (!empty($c_certData))
                   {  
                       $c_certtype = $v->getCertType();
                       if ($c_certtype == 'X509Certificate')
                       {
                          $c_fingerprint = $v->getFingerprint();
                          $c_certValid = validateX509($c_certData);
                          if (!$c_certValid)
                          {
                             $cString .='<span class="error">' . lang('rr_certificatenotvalid') . '</span>';
                          }
                        }
                        if (!empty($c_fingerprint))
                        {
                           $cString .='<b>'.lang('rr_fingerprint').':</b> <span>' . $c_fingerprint . '</span><br />';
                        }
                        $cString .= '<span class="accordionButton"><b>'.lang('rr_certbody').'</b><br /></span><code class="accordionContent">' . trim($c_certData) . '</code>';
                        
                   
                    }
                       $cString .= '<br />';
               }
               $d[$i]['value'] = $cString;
           }
       } 
       if($sppart)
       {
           $d[++$i]['name'] = 'Certificates in SPSSODescriptor';
           $cString = '';
           if(array_key_exists('spsso',$certs))
           {
               foreach($certs['spsso'] as $v)
               {
                   $certusage = $v->getCertuse();
                   if(empty($certusage))
                   {
                      $cString .= '<i>signing/encryption</i><br />';
                   }
                   else
                   {
                      $cString .= '<i>'.$certusage.'</i><br />';
                   }
                   $kname = $v->getKeyname();
                   $c_certData = $v->getCertData();
                   if(!empty($kname))
                   {
                     $cString .='<b>'.lang('rr_keyname').':</b><br /> ' . str_replace(',','<br />',$kname) . '<br />';
                   }
                   if (!empty($c_certData))
                   {  
                       $c_certtype = $v->getCertType();
                       if ($c_certtype == 'X509Certificate')
                       {
                          $c_fingerprint = $v->getFingerprint();
                          $c_certValid = validateX509($c_certData);
                          if (!$c_certValid)
                          {
                             $cString .='<span class="error">' . lang('rr_certificatenotvalid') . '</span>';
                          }
                        }
                        if (!empty($c_fingerprint))
                        {
                           $cString .='<b>'.lang('rr_fingerprint').':</b> <span>' . $c_fingerprint . '</span><br />';
                        }
                        $cString .= '<span class="accordionButton"><b>'.lang('rr_certbody').'</b><br /></span><code class="accordionContent">' . trim($c_certData) . '</code>';
                        
                   
                    }
                       $cString .= '<br />';
               }
               $d[$i]['value'] = $cString;
           }
       } 
       /**
        * end certs
        */
       $result[] = array('section'=>'certs','title'=>'Certificates','data'=>$d);
       $d=array();
       $i = 0;
       $d[++$i]['header'] = lang("rr_contacts");
       $contacts = $ent->getContacts(); 
       foreach ($contacts as $c)
       {
           $d[++$i]['name'] = $c->getType();
           $d[$i]['value'] = $c->getFullName() . " " . safe_mailto($c->getEmail());
       }
       $result[] = array('section'=>'contacts','title'=>'Contacts','data'=>$d);
       $d=array();
       $i = 0;
       if($idppart)
        {
            $d[++$i]['header'] = '<a name="arp"></a>' . lang('rr_arp');
            $encoded_entityid = base64url_encode($ent->getEntityId());
            $arp_url = base_url() . 'arp/format2/' . $encoded_entityid . '/arp.xml';
            $d[++$i]['name'] = lang('rr_individualarpurl');
            $d[$i]['value'] = '<span class="accordionButton">'.lang('rr_arpurl').'</span><span class="accordionContent"><br />' . $arp_url . '&nbsp;</span>&nbsp;' . anchor_popup($arp_url, '<img src="' . base_url() . 'images/icons/arrow.png"/>');
        //
        
            $exc = $ent->getExcarps();
            if(!$locked && $has_write_access && $ent->getLocal())
            {
                $mlink = '<a href="'.base_url().'manage/arpsexcl/idp/'.$ent->getId().'"><span class="lbl lbl-alert">' . lang('rr_editarpexc') . '</span></a>';
            $d[++$i]['name'] = lang('rr_arpexclist_title') .' '.$mlink;
            if(is_array($exc) && count($exc)>0)
            {
                $l = '<ul>';
                foreach($exc as $e)
                {
                   $l .= '<li>'.$e.'</li>';
                }
                $l .= '</ul>';
                $d[$i]['value'] = $l;
           }
           else
           {
              $d[$i]['value'] = '';
           }

          }
             $d[++$i]['name'] = lang('rr_arpoverview');
             $d[$i]['value'] = anchor(base_url('reports/idp_matrix/show/'.$ent->getId()),'matrix');
           
            
         }
       /**
        * supported attributes by IDP part
        */
       if($idppart)
       {
          $image_link = '<img src="' . base_url() . 'images/icons/pencil-field.png"/>';
          if($has_write_access)
          {     
              $edit_attributes = '<a href="' . base_url() . 'manage/supported_attributes/idp/' . $id . ' " class="edit"><span class="lbl lbl-edit">' . $image_link . lang('rr_edit').'</span></a>';
              $edit_policy = '<a href="' . base_url() . 'manage/attribute_policy/globals/' . $id . ' " class="edit"><span class="lbl lbl-edit">' . $image_link . lang('rr_edit').'</span></a>';
          }
          
          $d[++$i]['header'] = '<a name="attrs"></a>' . lang('rr_supportedattributes') . ' ' . $edit_attributes;
          $tmpAttrs = new models\AttributeReleasePolicies;
          $supportedAttributes = $tmpAttrs->getSupportedAttributes($ent);
          foreach ($supportedAttributes as $s)
          {
              $d[++$i]['name'] = $s->getAttribute()->getName();
              $d[$i]['value'] = $s->getAttribute()->getDescription();
          }

          $d[++$i]['header'] = lang('rr_defaultspecificarp') . $edit_policy;
          $disable_caption = true;
          $d[++$i]['2cols'] = $this->show_element->generateTableDefaultArp($ent, $disable_caption);
       } 
       /**
        * required attributes by SP part
        */
       if($sppart)
       {
           $edit_req_attrs_link = '';
          
           if ($has_write_access)
           {
               $d[++$i]['name'] = lang('rr_attrsoverview');
               $d[$i]['value'] = anchor(base_url().'reports/sp_matrix/show/'.$ent->getId(),lang('rr_attrsoverview'));

              $image_link = '<img src="' . base_url('images/icons/pencil-field.png') . '"/>';
              $edit_req_attrs_link = '<a href="' . base_url() . 'manage/attribute_requirement/sp/' . $ent->getId() . '" class="edit" title="edit" ><span class="lbl lbl-edit">' . genIcon('edit') . lang('rr_edit').'</span></a>';
           }
           $d[++$i]['header'] = '<span id="reqattrs"></span>' . lang('rr_requiredattributes') . $edit_req_attrs_link;
           $requiredAttributes = $ent->getAttributesRequirement();
           if ($requiredAttributes->count() === 0)
           {
               $d[++$i]['name'] = '';
               $d[$i]['value'] = '<span class="notice">'.lang('rr_noregspecified_inherit_from_fed').'</span>';
           }
           else
           {
               foreach ($requiredAttributes as $v)
               {
                   $d[++$i]['name'] = $v->getAttribute()->getName();
                   $d[$i]['value'] = '<b>' . $v->getStatus() . '</b>: <i>(' . $v->getReason() . ')</i>';
               }
            }
           

       }
        $result[] = array('section'=>'attrs','title'=>'Attributes','data'=>$d);   
        $d = array();
        $i = 0;
       
       $d[++$i]['header'] = lang('rr_uii');
       
       if($idppart)
       {
          $uiiarray = array();
          $d[++$i]['2cols'] = lang('rr_uii').' for IDP part';
          foreach($extend as $e)
          {
             if($e->getNamespace() == 'mdui' && $e->getType() == 'idp')
             { 
                $uiiarray[$e->getElement()][] = $e;
             }
          }
          $d[++$i]['name'] = 'DisplayName';
          if(isset($uiiarray['DisplayName']))
          {
             $str = '';
             foreach($uiiarray['DisplayName'] as $v)
             {
                 $attr = $v->getAttributes();
                 $str .= '<b>'.$attr['xml:lang'].':</b> '.$v->getEvalue().'<br />';
                  
             }
             $d[$i]['value'] = $str;
          }
          else
          {
             $d[$i]['value'] = 'not set';
          }
          $d[++$i]['name'] = 'Description';
          if(isset($uiiarray['Description']))
          {
             $str = '';
             foreach($uiiarray['Description'] as $v)
             {
                 $attr = $v->getAttributes();
                 $str .= '<b>'.$attr['xml:lang'].':</b> '.$v->getEvalue().'<br />';
                  
             }
             $d[$i]['value'] = $str;
          }
          else
          {
             $d[$i]['value'] = 'not set';
          }
          $d[++$i]['name'] = 'PrivacyStatementURL';
          if(isset($uiiarray['PrivacyStatementURL']))
          {
             $str = '';
             foreach($uiiarray['PrivacyStatementURL'] as $v)
             {
                 $attr = $v->getAttributes();
                 $str .= '<b>'.$attr['xml:lang'].':</b> '.$v->getEvalue().'<br />';
                  
             }
             $d[$i]['value'] = $str;
          }
          else
          {
             $d[$i]['value'] = 'not set';
          }
          $d[++$i]['name'] = 'InformationURL';
          if(isset($uiiarray['InformationURL']))
          {
             $str = '';
             foreach($uiiarray['InformationURL'] as $v)
             {
                 $attr = $v->getAttributes();
                 $str .= '<b>'.$attr['xml:lang'].':</b> '.$v->getEvalue().'<br />';
                  
             }
             $d[$i]['value'] = $str;
          }
          else
          {
             $d[$i]['value'] = 'not set';
          }
           $logoatts = array(
              'width'      => '400',
              'height'     => '200',
              'scrollbars' => 'yes',
              'status'     => 'yes',
              'resizable'  => 'yes',
              'screenx'    => '0',
              'screeny'    => '0'
            );
          $d[++$i]['name'] = 'Logos';
          if(isset($uiiarray['Logo']))
          {
             $str = '';
             foreach($uiiarray['Logo'] as $v)
             {
                 $str .= @anchor_popup($v->getLogoValue(),$v->getLogoValue(), $logoatts).'<br />';
                  
             }
             $d[$i]['value'] = $str;
          }
          else
          {
             $d[$i]['value'] = 'not set';
          }
          $d[++$i]['name'] = 'GeoLocation';
          if(isset($uiiarray['GeolocationHint']))
          {
             $str = '';
             foreach($uiiarray['GeolocationHint'] as $v)
             {
                 $str .= $v->getElementValue().'<br />';
                  
             }
             $d[$i]['value'] = $str;
          }
          else
          {
             $d[$i]['value'] = 'not set';
          }
          
       }
       if($sppart)
       {
          $uiiarray = array();
          $d[++$i]['2cols'] = lang('rr_uii').' for SP part';
          foreach($extend as $e)
          {
             if($e->getNamespace() == 'mdui' && $e->getType() == 'sp')
             { 
                $uiiarray[$e->getElement()][] = $e;
             }
          }
          $d[++$i]['name'] = 'DisplayName';
          if(isset($uiiarray['DisplayName']))
          {
             $str = '';
             foreach($uiiarray['DisplayName'] as $v)
             {
                 $attr = $v->getAttributes();
                 $str .= '<b>'.$attr['xml:lang'].':</b> '.$v->getEvalue().'<br />';
                  
             }
             $d[$i]['value'] = $str;
          }
          else
          {
             $d[$i]['value'] = 'not set';
          }
          $d[++$i]['name'] = 'Description';
          if(isset($uiiarray['Description']))
          {
             $str = '';
             foreach($uiiarray['Description'] as $v)
             {
                 $attr = $v->getAttributes();
                 $str .= '<b>'.$attr['xml:lang'].':</b> '.$v->getEvalue().'<br />';
                  
             }
             $d[$i]['value'] = $str;
          }
          else
          {
             $d[$i]['value'] = 'not set';
          }
          $d[++$i]['name'] = 'PrivacyStatementURL';
          if(isset($uiiarray['PrivacyStatementURL']))
          {
             $str = '';
             foreach($uiiarray['PrivacyStatementURL'] as $v)
             {
                 $attr = $v->getAttributes();
                 $str .= '<b>'.$attr['xml:lang'].':</b> '.$v->getEvalue().'<br />';
                  
             }
             $d[$i]['value'] = $str;
          }
          else
          {
             $d[$i]['value'] = 'not set';
          }
          $d[++$i]['name'] = 'InformationURL';
          if(isset($uiiarray['InformationURL']))
          {
             $str = '';
             foreach($uiiarray['InformationURL'] as $v)
             {
                 $attr = $v->getAttributes();
                 $str .= '<b>'.$attr['xml:lang'].':</b> '.$v->getEvalue().'<br />';
                  
             }
             $d[$i]['value'] = $str;
          }
          else
          {
             $d[$i]['value'] = 'not set';
          }
          $d[++$i]['name'] = 'Logos';
          if(isset($uiiarray['Logo']))
          {
             $str = '';
             foreach($uiiarray['Logo'] as $v)
             {
                 $str .= anchor($v->getLogoValue()).'<br />';
                  
             }
             $d[$i]['value'] = $str;
          }
          else
          {
             $d[$i]['value'] = 'not set';
          }
          $d[++$i]['name'] = 'GeoLocation';
          if(isset($uiiarray['GeolocationHint']))
          {
             $str = '';
             foreach($uiiarray['GeolocationHint'] as $v)
             {
                 $str .= $v->getElementValue().'<br />';
                  
             }
             $d[$i]['value'] = $str;
          }
          else
          {
             $d[$i]['value'] = 'not set';
          }
       }
       
        $result[] = array('section'=>'uii','title'=>'UII','data'=>$d);   
        $d = array();
        $i = 0;

       $d[++$i]['header'] = lang('rr_logs');
       $d[++$i]['name'] = lang('rr_modifications');
       $d[$i]['value'] = $this->show_element->generateModificationsList($ent, 3);
       if($idppart)
       {
       $tmp_logs = new models\Trackers;
           $arp_logs = $tmp_logs->getArpDownloaded($ent);

            $logg_tmp = '<ul>';
            if (!empty($arp_logs))
            {
                foreach ($arp_logs as $l)
                {
                    $logg_tmp .= '<li><b>'. $l->getCreated()->format('Y-m-d H:i:s') . '</b> - ' . $l->getIp() .' <small><i>(' . $l->getAgent() .')</i></small></li>';
                }   
             }
             $logg_tmp .= '</ul>';
             $d[++$i]['name'] = lang('rr_recentarpdownload');
             $d[$i]['value'] = $logg_tmp;
       }
        $result[] = array('section'=>'logs','title'=>'Logs','data'=>$d);   
        $d = array();
        $i = 0;



       $d[++$i]['header'] = 'Management';
       $d[++$i]['name'] = lang('rr_managestatus');
       if ($has_manage_access)
       {
          $d[$i]['value'] = lang('rr_lock').'/'.lang('rr_unlock').' '.lang('rr_enable').'/'.lang('rr_disable').' '. anchor(base_url() . 'manage/entitystate/modify/' . $id , '<img src="' . base_url() . 'images/icons/arrow.png"/>');
       }
       else
       {
          $d[$i]['value'] = lang('rr_lock').'/'.lang('rr_unlock').' '.lang('rr_enable').'/'.lang('rr_disable').' <img src="' . base_url() . 'images/icons/prohibition.png"/>';
       }
       $d[++$i]['name'] = '';
       if ($has_manage_access)
       {
            $d[$i]['value'] = lang('rr_displayaccess') . anchor(base_url() . 'manage/access_manage/entity/' . $id, '<img src="' . base_url() . 'images/icons/arrow.png"/>');
       }
       else
       {
            $d[$i]['value'] = lang('rr_displayaccess') . '<img src="' . base_url() . 'images/icons/prohibition.png"/>';
       }
        $result[] = array('section'=>'mngt','title'=>'Management','data'=>$d);   
        $d = array();
        $i = 0;
       
      
       $data['tabs'] = $result; 
       $data['content_view'] = 'providers/detail_view.php';
       $this->load->view('page',$data); 
    }
    
    function showmembers($providerid)
    {
       if (!$this->input->is_ajax_request())
       {
           show_error('Request not allowed',403);
       }
       $ent=$this->em->getRepository("models\Provider")->findOneBy(array('id'=>$providerid));
       if(empty($ent))
       {
           show_error('Provider not found',404);
       }
       else
       {
           $has_read_access = $this->zacl->check_acl($providerid, 'read', 'entity', '');
           if(!$has_read_access)
           {
              show_error('Access denied',403);
           }

           $tmp_providers = new models\Providers;
           $type = $ent->getType();
           if($type === 'SP')
           {
               $members = $tmp_providers->getCircleMembersIDP($ent);
           }
           elseif($type === 'IDP')
           {
               $members = $tmp_providers->getCircleMembersSP($ent);

           }
           else
           {
               $members = $tmp_providers->getCircleMembers($ent);
           }
           if(empty($members))
           {
              $l[] = array('entityid'=>'No members','name'=>'','url'=>'');
           }
           $preurl = base_url().'providers/detail/show/';
           foreach($members as $m)
           {
              $name = $m->getName();
              if(empty($name))
              {
                 $name = $m->getEntityId();
              }
               $l[] = array('entityid'=>$m->getEntityId(),'name'=>$name,'url'=>$preurl.$m->getId());
           }
           echo json_encode($l);

       }
    }

}
