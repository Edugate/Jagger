<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2012, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * Idp_matrix Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Idp_matrix extends MY_Controller
{
    private $tmp_providers; 
    private $logo_url;
    


    function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->library('table');
        $this->load->library('arp_generator');
        $this->tmp_providers = new models\Providers;
        $this->current_site = current_url();
        $this->logo_basepath = $this->config->item('rr_logouriprefix');
        $this->logo_baseurl = $this->config->item('rr_logobaseurl');
        if (empty($this->logo_baseurl))
        {
            $this->logo_baseurl = base_url();
        }
        $this->logo_url = $this->logo_baseurl . $this->logo_basepath;

    }

    private function _get_members($idp)
    {
        $members = $this->tmp_providers->getCircleMembersSP($idp);
        return $members;
    }
    public function show($idpid)
    {
        $loggedin = $this->j_auth->logged_in();
        if ($loggedin)
        {
            $this->session->set_userdata(array('currentMenu' => 'awaiting'));
            $this->load->library('zacl');
        } else
        {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'refresh');
        }
	if(empty($idpid) OR !is_numeric($idpid))
        {
            show_error('Wrong or empty id', 404);
        }
        $idp = $this->tmp_providers->getOneIdpById($idpid);
        if(empty($idp))
        {
            show_error('Identity Provider not found',404);
        }
        $members = $this->_get_members($idp);
        $arparray = $this->arp_generator->arpToXML($idp,TRUE);

        $extends = $idp->getExtendMetadata();
        if (count($extends) > 0)
        {
            $is_logo = false;
            foreach ($extends as $ex)
            {
                $el = $ex->getElement();
                if ($el == 'Logo')
                {
                    $data['provider_logo_url'] = $this->logo_url . $ex->getEvalue();
                }
            }
        }
        if(empty($arparray))
        {
            $data['content_view'] = 'reports/idp_matrix_show_view';
            $data['entityid'] = $idp->getEntityId();
            $data['idpid'] = $idp->getId();
            $data['error_message'] = 'To generate matrix IDP needs to support at least one attribute';
            $this->load->view('page',$data); 
            return;
        }
        $attrs = $this->em->getRepository("models\Attribute")->findAll();
        $attrmatrix_template = array();
        foreach($attrs as $a)
        {
          $attrmatrix_template[$a->getName()] = null;
        }
        foreach($arparray as $spk=>$spv)
        {
           /**
            *  $spv['attributes'] - array zawierajaca wszystkie supportowane attributy z polityka
            *  czyli $arparrat['entityid']['attributes']
            */
           $mrows[$spk] = $attrmatrix_template;
           foreach($mrows[$spk] as $k=>$p)
           {
              $mrows[$spk][$k] = '<div class="dis">&nbsp;</div>';
           }
           if(array_key_exists('attributes',$spv))
           {
              foreach($spv['attributes'] as $attrkey=>$attrvalue)
              {
                   if(isset($attrvalue))
                   {
                      $mrows[$spk][$attrkey] = '<div class="den">&nbsp</div>';
                   }
                   else
                   {
                      $mrows[$spk][$rkey] = '<div class="perm">'.$attrvalue.'</div>';
                   }
              }
           }
           if(array_key_exists('req',$spv))
           {
                 foreach($spv['req'] as $rkey=>$rvalue)
                 {
                     if($rvalue=='required')
                     {
                         if(array_key_exists($rkey,$spv['attributes']))
                         {
                             if(!empty($spv['attributes'][$rkey]))
                             {
                                  if(array_key_exists($rkey,$spv['custom']))
                                  {
                                      $mrows[$spk][$rkey] = '<div class="spec">R</div>';
                                  }
                                  else
                                  {       
                                      $mrows[$spk][$rkey] = '<div class="perm">R</div>';
                                  }
                             }
                             else
                             {
                                  $mrows[$spk][$rkey] = '<div class="den">R</div>';
                             }
                         }
                         else
                         {
                                  $mrows[$spk][$rkey] = '<div class="dis">R</div>';
                         }
                     }
                     elseif($rvalue=='desired')
                     {
                         if(array_key_exists($rkey,$spv['attributes']))
                         {
                             if(!empty($spv['attributes'][$rkey]))
                             {
                                  if(array_key_exists($rkey,$spv['custom']))
                                  {
                                      $mrows[$spk][$rkey] = '<div class="spec">D</div>';
                                  }
                                  else
                                  {       
                                      $mrows[$spk][$rkey] = '<div class="perm">D</div>';
                                  }
                             }
                             else
                             {
                                  $mrows[$spk][$rkey] = '<div class="den">D</div>';
                             }
                         }
                         else
                         {
                                  $mrows[$spk][$rkey] = '<div class="dis">D</div>';
                         }
                     }
                 }
           }
        }
        foreach($mrows as $key=>$value)
        {
          $t = null;
          if(!empty($arparray[$key]['name']))
          {
             $t = '<a href="'.base_url().'providers/provider_detail/sp/'.$arparray[$key]['spid'].'" title="'.$key.'">'.substr($arparray[$key]['name'],0,30).'</a>';
          }
          else
          {
             $t = '<a href="'.base_url().'providers/provider_detail/sp/'.$arparray[$key]['spid'].'" title="'.$key.'">'.substr($key,0,30).'</a>';
          }
          array_unshift($mrows[$key],$t);
          
        }
        //$thead = array_keys($attrmatrix_template);
        foreach($attrmatrix_template as $k=>$v)
        {
        //  $thead[] = '<span style="-moz-transform: rotate(-90deg)">'.$k.'</span>';
          $thead[] = '
                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="160">
                  <text id="thetext" transform="rotate(270, 9, 0) translate(-150,0)">'.htmlentities($k).'</text>
                </svg>
               ';

        }
        $corner = '<img src="'.base_url().'images/legend.png" />';
        $corner .= 'Service Provider';
        array_unshift($thead,$corner);
        array_unshift($mrows,$thead);
        $data['entityid'] = $idp->getEntityId();
        $data['idpid'] = $idp->getId();
        $data['result'] = $mrows;


        $data['content_view'] = 'reports/idp_matrix_show_view';
        $this->load->view('page',$data); 
        
    }


}
