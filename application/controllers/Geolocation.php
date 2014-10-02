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

    function __construct()
    {
        parent::__construct();
        $loggedin = $this->j_auth->logged_in();
        $this->current_site = current_url();
        if (!$loggedin)
        {
            $this->session->set_flashdata('target', $this->current_site);
            redirect('auth/login', 'location');
        }
        $this->load->library(array('form_element', 'form_validation', 'zacl', 'tracker'));
    }

    private function _submit_validate()
    {

        $this->form_validation->set_rules('latinput', lang('rr_latitude'), 'numeric|xss_clean');
        $this->form_validation->set_rules('lnginput', lang('rr_longitude'), 'numeric|xss_clean');
        return $this->form_validation->run();
    }

    public function show($entity = null, $type = null)
    {
        $data = array();
        if (empty($entity) || !is_numeric($entity) || empty($type))
        {
            show_error(lang('rerror_providernotexist'), 404);
        }
        if (!($type === 'idp' || $type === 'sp'))
        {
            show_error(lang('rerror_incorrectenttype'), 404);
        }

        $tmp_providers = new models\Providers;
        $provider = $tmp_providers->getOneById($entity);
        if (empty($provider))
        {
            show_error(lang('rerror_providernotexist'), 404);
        }
        $providerType = strtolower($provider->getType());
        if (!($providerType == $type || $providerType == 'both'))
        {
            show_error(lang('rerror_incorrectenttype'), 404);
        }
        $locked = $provider->getLocked();
        if ($locked)
        {
            $lockicon = '<img src="' . base_url() . 'images/icons/lock.png" title="' . lang('rr_locked') . '"/>';
        }
        else
        {
            $lockicon = '';
        }
        $hasWriteAccess = $this->zacl->check_acl($provider->getId(), 'write', 'entity', '');
        $hasReadAccess = $this->zacl->check_acl($provider->getId(), 'read', 'entity', '');
        if (!$hasWriteAccess)
        {
            $data = array(
                'content_view'=> 'nopermission',
                'error'=>lang('rrerror_noperm_geo') . ': ' . $provider->getEntityid()
            );
            $this->load->view('page', $data);
            return;
        }
        if ($this->_submit_validate()===TRUE)
        {
            if ($locked)
            {
                show_error(lang('error_lockednoedit'), 403);
            }
            $s_action = $this->input->post('addPoint');
            $s_raction = $this->input->post('remove');
            $s_idpid = $this->input->post('idp');
            $s_latinput = $this->input->post('latinput');
            $s_lnginput = $this->input->post('lnginput');
            $s_geoloc = $this->input->post('geoloc');
            $e_value = '' . $s_latinput . ',' . $s_lnginput . '';
            if (!empty($s_action) && !empty($s_idpid) && !empty($s_latinput) && !empty($s_lnginput))
            {
                $pid = $provider->getId();
                if ($s_idpid == $pid)
                {
                    $newgeo = new models\ExtendMetadata;
                    $ex = $this->em->getRepository("models\ExtendMetadata")->findOneBy(array('provider' => $s_idpid, 'namespace' => 'mdui', 'etype' => $type, 'parent' => null, 'element' => 'DiscoHints'));
                    if (empty($ex))
                    {
                        $ex = new models\ExtendMetadata;
                        $ex->setNamespace('mdui');
                        $ex->setElement('DiscoHints');
                        $ex->setProvider($provider);
                        $ex->setAttributes(array());
                        $ex->setType($type);
                        $this->em->persist($ex);
                    }
                    $newgeo->setParent($ex);
                    $newgeo->setElement('GeolocationHint');
                    $newgeo->setNamespace('mdui');
                    $newgeo->setValue($e_value);
                    $newgeo->setProvider($provider);
                    $newgeo->setAttributes(array());
                    $newgeo->setType($type);
                    $this->em->persist($newgeo);
                    $track_det = array('geo' => array('before' => '', 'after' => $e_value));
                    $this->tracker->save_track(strtolower($provider->getType()), 'modification', $provider->getEntityId(), serialize($track_det), FALSE);
                    $this->em->flush();
                }
            }
            elseif (!empty($s_raction) && $s_raction === 'remove' && !empty($s_geoloc))
            {
                if (is_array($s_geoloc))
                {
                    if (count($s_geoloc) > 0)
                    {


                        $geolocations = $this->em->getRepository("models\ExtendMetadata")->findBy(array('provider' => $provider->getId(), 'etype' => $type, 'namespace' => 'mdui', 'element' => 'GeolocationHint', 'evalue' => $s_geoloc));

                        if (count($geolocations) > 0)
                        {
                            $g_values = '';
                            foreach ($geolocations as $g)
                            {
                                $g_values .= $g->getElementValue() . '; ';
                                $this->em->remove($g);
                            }
                            $track_det = array('geo' => array('before' => $g_values, 'after' => ''));
                            $this->tracker->save_track(strtolower($provider->getType()), 'modification', $provider->getEntityId(), serialize($track_det), FALSE);

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
        if (empty($tmp_name))
        {
            $display_name = $provider->getEntityId();
        }
        else
        {
            $display_name = $provider->getName();
        }


        $data['subtitlepage'] = lang('rr_geolocation');
        $data['titlepage'] = anchor(base_url() . 'providers/detail/show/' . $provider->getId(), $display_name);
        $data['subtitle'] = '<div id="subtitle"><h3>' . $lockicon . ' &nbsp;&nbsp;' . anchor(base_url() . 'providers/detail/show/' . $provider->getId(), $display_name) . '<h3><h4>' . $provider->getEntityId() . '</h4> </div>';
        $data['form_errors'] = validation_errors('<p class="error">', '</p>');


        $extends = $provider->getExtendMetadata();
        $geolocations = array();
        if (count($extends) > 0)
        {
            foreach ($extends as $e)
            {
                $element = $e->getElement();
                $etype = $e->getType();
                if ($element === 'GeolocationHint' && $etype == $type)
                {
                    $geolocations[] = $e;
                }
            }
        }
        if (count($geolocations) > 0)
        {
            foreach ($geolocations as $geo)
            {
                $point = explode(",", $geo->getEvalue());
                $this->gmap->addMarkerByCoords($point['1'], $point['0'], $point['0'] . ',' . $point['1'], $display_name . " (" . $provider->getEntityId() . ")");
            }
        }
        else
        {
            $geocenter = $this->config->item('geocenterpoint');
            if (!empty($geocenter))
            {
                $this->gmap->adjustCenterCoords($geocenter['0'], $geocenter['1']);
            }
            else
            {
                $this->gmap->adjustCenterCoords('-6.247856140071235', '53.34961629053703');
            }
            $this->gmap->setZoomLevel(7);
        }


        $data['content_view'] = 'geomap_view';
        $content = "";



        $data['headerjs'] = $this->gmap->getHeaderJS();
        $data['headermap'] = $this->gmap->getMapJS();

        $content .= $data['onload'] = $this->gmap->printOnLoad();

        $content .= $data['map'] = $this->gmap->printMap();
        $content .= '<div class="small">' . lang('rr_latitude') . ': <span id="latspan"></span>&nbsp;&nbsp;' . lang('rr_longitude') . ': <span id="lngspan"></span> </div>';

        $hidden = array('idp' => $provider->getId());
        $action = current_url();


        $formular = '<div class="geoform" >';
        $errors_v = validation_errors('<div>', '</div>');
        if (!empty($errors_v))
        {
            $formular .= '<div data-alert class="alert-box alert">' . $errors_v . '</div>';
        }
        $spacebreak = '<div><hr class="span-23" /></div>';

        $formular .= form_open($action, '', $hidden);
        $formular .='<div class="small-12 columns"><div class="large-3 columns"><label for="latinput">' . lang('rr_latitude') . '</label></div><div class="large-9 columns" ><input type="text" id="latinput"  name="latinput" value="" /></div></div>
<div class="small-12 columns"><div class="large-3 columns"><label for="lnginput">' . lang('rr_longitude') . '</label></div><div class="large-9 columns" ><input type="text" id="lnginput" name="lnginput" value="" /></div></div> ';
        if ($locked)
        {
            $formular .='<div class="buttons"><button type="submit" name="addPoint" id="addPoint" value="add geolocation" class="addbutton addicon" disabled="disabled">' . lang('rerror_cannotaddpoint') . ' (' . lang('rr_locked') . ')</button></div>';
        }
        else
        {
            $formular .='<div class="buttons"><button type="submit" name="addPoint" id="addPoint" value="add geolocation" class="addbutton addicon">' . lang('rr_addpoint') . '</button></div>';
        }
        $formular .= form_close();
        $formular .='</div>';


        $formular2 = '<div class="geoform" >' . form_open();

        foreach ($geolocations as $g)
        {
            $formular2 .= '<div class="small-12 columns" >';
            $formular2 .= '<div class="small-1 columns"><input type="checkbox" name="geoloc[]" id="geoloc[]" value=' . $g->getEvalue() . '  ></div>';
            $formular2 .= '<div class="small-10 columns end"><input type="text" disabled="disabled" name="info" id="info" value=' . $g->getEvalue() . ' /></div>';
            $formular2 .= '</div>';
        }
        if ($locked)
        {
            $formular2 .= '<div class="buttons"><button type="submit" name="remove" value="remove" class="resetbutton reseticon" disabled="disabled">' . lang('rerror_cannotdelete') . ' (' . lang('rr_locked') . ')</button></div>';
        }
        else
        {
            $formular2 .= '<div class="buttons"><button type="submit" name="remove" value="remove" class="resetbutton reseticon alert">' . lang('rr_rmselectedpoints') . '</button></div>';
        }
        $formular2 .= form_close() . '</div>';

        $formulars = $formular . $spacebreak;
        if (count($geolocations) > 0)
        {
            $formulars .= $formular2 . $spacebreak;
        }


        $data['formulars'] = $formulars;
        $data['mapdiv'] = $content;
        $data['loadGoogleMap'] = true;

        $data['provider_id'] = $provider->getId();
        $data['type'] = $type;

        $this->load->view('page', $data);
    }

}
