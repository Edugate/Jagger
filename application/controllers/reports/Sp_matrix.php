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
 * Sp_matrix Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Sp_matrix extends MY_Controller
{
    private $tmp_providers; 


    function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->library('table');
        $this->tmp_providers = new models\Providers;
        $this->current_site = current_url();
    }

    private function _get_members($sp, $onlylocal=FALSE)
    {
        $members = $this->tmp_providers->getCircleMembersIDP($sp, NULL, $onlylocal);
        return $members;
    }
    public function members_urls($spid)
    {
	if(empty($spid) || !is_numeric($spid))
        {
            show_error('Wrong or empty id', 404);
        }
        $sp = $this->tmp_providers->getOneSpById($spid);
        
        if(empty($sp))
        {
            show_error('Service Provider not found',404);
        }
        $sp_entityid = $sp->getEntityId();
        $members = $this->_get_members($sp,TRUE);
        $tmp_attributes = new models\Attributes();
        $attributes = $tmp_attributes->getAttributes();
        $tmp_reqs = new models\AttributeRequirements;
        $req_attributes = $tmp_reqs->getRequirementsBySP($sp);
        $requirement = array();
        $output = new \DOMDocument() ;
        $o = $output->CreateElement('Details');
        $output->appendChild($o);
        if(count($req_attributes)>0)
        {
           foreach($req_attributes as $r)
           {
              $attr = $r->getAttribute()->getName();
              
              $requirement[$attr] = $r->getStatus();
           }
           $re = $output->CreateElement('Requirement');
           $o->appendChild($re);
           foreach($requirement as $key=>$value)
           {
               $a=$output->CreateElement('Attribute');
               $attrn=$output->CreateElement('AttributeName',$key);
               $st=$output->CreateElement('Status',$value);
               $re->appendChild($a);
               $a->appendChild($attrn);
               $a->appendChild($st);
           }
        } 
        if(count($members)>0)
        {
           $sites = $output->CreateElement('sites');
           $o->appendChild($sites);
           foreach($members as $m)
           {
              $excluded = $m->getExcarps();
              if( in_array($sp_entityid,$excluded))
              {
                 continue;
              }
              $site = $output->CreateElement('site');
              $entityname = $m->getName();
              if(empty($entityname))
              {
                  $entityname = $m->getEntityid();
              }
              $name = $output->createElement('Name');
              $name->appendChild( $output->createTextNode(''.$entityname.''));
              $entityid = $output->CreateElement('entityID');
              $entityid->appendChild( $output->createTextNode($m->getEntityid()));
              $providerurl = $output->CreateElement('providerURL',base_url().'providers/detail/show/'.$m->getId());
              $arp_url = base_url().'arp/format2/'.base64url_encode($m->getEntityId()).'/arp.xml';
              $location = $output->CreateElement('Location',$arp_url);
              $sites->appendChild($site);
              $site->appendChild($name);
              $site->appendChild($entityid);
              $site->appendChild($providerurl);
              $site->appendChild($location); 
           }
           
        }
           $this->output->set_content_type('text/xml');
           $data['out']=$output->saveXML();
           $this->load->view('metadata_view', $data);
    }
    public function show($spid)
    {
        $loggedin = $this->j_auth->logged_in();
        if ($loggedin)
        {
            $this->session->set_userdata(array('currentMenu' => 'awaiting'));
            $this->load->library('zacl');
        } else
        {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        }
	if(empty($spid) || !is_numeric($spid))
        {
            show_error('Wrong or empty id', 404);
        }
        $sp = $this->tmp_providers->getOneSpById($spid);
        if(empty($sp))
        {
            show_error('Service Provider not found',404);
        }
        $members = $this->_get_members($sp);
        $lang = MY_Controller::getLang();
        $titlename = $sp->getNameToWebInLang($lang, $sp->getType());
        $data['titlepage'] = '<a href="' . base_url() . 'providers/detail/show/' . $sp->getId() . '">' .$titlename . '</a>';
        $data['subtitlepage'] = lang('rr_provideingattrsoverview');

        $this->title = $titlename;
        $cache_time = $this->config->item('arp_cache_time');
        $data['arpcachetimeicon'] = showBubbleHelp('ARPs are cached for '.$cache_time.' seconds');
        $data['load_matrix_js'] = TRUE; 
        $data['sites_url'] = base_url().'reports/sp_matrix/members_urls/'.$spid;
        $data['entityid'] = $sp->getEntityId();
        $data['entityname'] = $sp->getName();
        
        $data['spid'] = $sp->getId();
        $data['content_view'] = 'reports/sp_matrix_show_view';
        $this->load->view('page',$data);


        
        
    }


}
