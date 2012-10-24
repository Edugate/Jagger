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
 * Geolocation Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Geolocation extends MY_Controller {

    function __construct() {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin) {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'refresh');
        }
        $this->load->library('form_element');
        $this->load->library('form_validation');
        $this->load->library('zacl');
    }

    private function _submit_validate() {

        $this->form_validation->set_rules('latinput', 'Latitude', 'numeric|xss_clean');
        $this->form_validation->set_rules('lnginput', 'Longitutde', 'numeric|xss_clean');
        return $this->form_validation->run();
    }

    public function show($entity = null, $type = null) {
        $data = array();
        if (empty($entity) or !is_numeric($entity) or empty($type)) {
            show_error('Provider Id : wrong or not provided', 404);
        }
        if (!($type == 'idp' or $type == 'sp')) {
            show_error('Wrong type of entity', 404);
        }

        $tmp_providers = new models\Providers;
        $provider = $tmp_providers->getOneById($entity);
        if (empty($provider)) {
            show_error('Provider not found', 404);
        }
        $provider_type = strtolower($provider->getType());
        if (!($provider_type == $type or $provider_type == 'both')) {
            show_error('Wrong type of provider', 404);
        }
        $locked = $provider->getLocked();
        if($locked)
        {
          $lockicon = '<img src="'.base_url().'images/icons/lock.png" title="Locked"/>';
        }
        else
        {
          $lockicon = '';
        }
        $has_write_access = $this->zacl->check_acl($provider->getId(), 'write', $type, '');
        $has_read_access = $this->zacl->check_acl($provider->getId(), 'read', $type, '');


        if (!$has_write_access) {
            $data['content_view'] = 'nopermission';
            $data['error'] = 'No access to edit provider\'s geolocations: ' . $provider->getEntityid();
            $this->load->view('page', $data);
            return;
        }
        if ($this->_submit_validate()) {
            if($locked)
            {
               show_error('Entity id Locked cannot be modified',403);
            }
            $s_action = $this->input->post('addPoint');
            $s_raction = $this->input->post('remove');
            $s_idpid = $this->input->post('idp');
            $s_latinput = $this->input->post('latinput');
            $s_lnginput = $this->input->post('lnginput');
            $s_geoloc = $this->input->post('geoloc');
            $e_value = '' . $s_latinput . ',' . $s_lnginput . '';
            if (!empty($s_action) && !empty($s_idpid) && !empty($s_latinput) && !empty($s_lnginput)) {
                if ($s_idpid == $provider->getId()) {
                    $newgeo = new models\ExtendMetadata;
                    $ex = $this->em->getRepository("models\ExtendMetadata")->findOneBy(array('provider' => $s_idpid, 'namespace' => 'mdui', 'parent' => null, 'element' => 'DiscoHints'));
                    if (empty($ex)) {
                        $ex = new models\ExtendMetadata;
                        $ex->setNamespace('mdui');
                        $ex->setElement('DiscoHints');
                        $ex->setProvider($provider);
                        $ex->setAttributes(array());
                        $ex->setType(strtolower($provider->getType()));
                        $this->em->persist($ex);
                    }
                    $newgeo->setParent($ex);
                    $newgeo->setElement('GeolocationHint');
                    $newgeo->setNamespace('mdui');
                    $newgeo->setValue($e_value);
                    $newgeo->setProvider($provider);
                    $newgeo->setAttributes(array());
                    $newgeo->setType(strtolower($provider->getType()));
                    $this->em->persist($newgeo);
                    $this->em->flush();
                }
            } elseif (!empty($s_raction) && $s_raction == 'remove' && !empty($s_geoloc)) {
                if (is_array($s_geoloc)) {
                    if (count($s_geoloc) > 0) {


                        $geolocations = $this->em->getRepository("models\ExtendMetadata")->findBy(array('provider' => $provider->getId(), 'namespace' => 'mdui', 'element' => 'GeolocationHint', 'evalue' => $s_geoloc));

                        if (count($geolocations) > 0) {
                            foreach ($geolocations as $g) {
                                $this->em->remove($g);
                            }
                            $this->em->flush();
                        }
                    }
                }
            }
        }

        $this->load->library('Jsmin');
        $this->load->library('Gmap');
        $this->gmap->GoogleMapAPI();
        $this->gmap->setMapType('roadmap');

        $tmp_name = $provider->getName();
        if (empty($tmp_name)) {
            $display_name = $provider->getEntityId();
        } else {
            $display_name = $provider->getName();
        }

        
        $data['subtitle'] = "<div id=\"subtitle\">".$lockicon." Geolocations for " . $display_name . "&nbsp;&nbsp;" . anchor(base_url() . "providers/provider_detail/" . $type . "/" . $provider->getId(), '<img src="' . base_url() . 'images/icons/home.png" />') . " </div>";
        $data['form_errors'] = validation_errors('<p class="error">', '</p>');


        $extends = $provider->getExtendMetadata();
        $geolocations = array();
        if (count($extends) > 0) {
            foreach ($extends as $e) {
                $element = $e->getElement();
                $etype = $e->getType();
                if ($element == 'GeolocationHint' && $etype == $type) {
                    $geolocations[] = $e;
                }
            }
        }
        if (count($geolocations) > 0) {
            foreach ($geolocations as $key => $geo) {
                $point = explode(",", $geo->getEvalue());
                $this->gmap->addMarkerByCoords($point['1'], $point['0'],$point['0'].','.$point['1'] , $display_name . " (" . $provider->getEntityId() . ")");
            }
        } else {
            $this->gmap->adjustCenterCoords('-6.247856140071235', '53.34961629053703');
            $this->gmap->setZoomLevel(7);
        }


        $data['content_view'] = 'geomap_view';
        $content = "";



        $data['headerjs'] = $this->gmap->getHeaderJS();
        $data['headermap'] = $this->gmap->getMapJS();

        $content .= $data['onload'] = $this->gmap->printOnLoad();

        $content .= $data['map'] = $this->gmap->printMap();
        $content .= '<div class="small">Lattitude: <span id="latspan"></span>&nbsp;&nbsp;Longitude: <span id="lngspan"></span> </div>';

        $hidden = array('idp' => $provider->getId());
        $action = current_url();
        $formular = '<span class="span-24 geoform" >';
        $errors_v = validation_errors('<span class="span-5">', '</span><br />');
        if (!empty($errors_v)) {
            $formular .= '<div class="error">' . $errors_v . '</div>';
        }
        $spacebreak = '<div class="span-23"><hr class="span-23" /></div>';

        $formular .= form_open($action, '', $hidden);
        $formular .=' <label for="latinput">Latitude</label><input type="text" id="latinput"  name="latinput" value="" /><br />
<label for="lnginput">Longitude</label><input type="text" id="lnginput" name="lnginput" value="" /><br /> ';
        if($locked)
        {
             $formular .='<div class="buttons"><button type="submit" name="addPoint" id="addPoint" value="add geolocation" class="btn positive" disabled="disabled"><span class="save">cannot add point (locked)</span></button></div>';
        }
        else
        {
             $formular .='<div class="buttons"><button type="submit" name="addPoint" id="addPoint" value="add geolocation" class="btn positive"><span class="save">add point</span></button></div>';
        }
        $formular .= form_close();
        $formular .='</span>';


        $formular2 = '<span class="span-24 geoform" >' . form_open();

        foreach ($geolocations as $g) {
            $formular2 .= '<input type="checkbox" name="geoloc[]" id="geoloc[]" value=' . $g->getEvalue() . ' >';
            $formular2 .= '<input type="text" disabled="disabled" name="info" id="info" value=' . $g->getEvalue() . ' /><br />';
        }
        if($locked)
        {
            $formular2 .= '<div class="buttons"><button type="submit" name="remove" value="remove" class="btn negative" disabled="disabled"><span class="remove">Cannot delete (locked)</span></button></div>';
           
        }
        else
        {
            $formular2 .= '<div class="buttons"><button type="submit" name="remove" value="remove" class="btn negative"><span class="remove">Remove selected points</span></button></div>';
        }
        $formular2 .= form_close() . '</span>';

        $formulars = $formular . $spacebreak ;
       if(count($geolocations) > 0)
       {
         $formulars .= $formular2 . $spacebreak;
       }


        $content = '<div class="span-12">' . $content . '</div>' . '<div class="span-11">' . $formulars . '</div>';
        $data['loadGoogleMap'] = true;
        $data['mapa'] = $content;

        $data['provider_id'] = $provider->getId();
        $data['type'] = $type;

        $this->load->view('page', $data);



    }


}

