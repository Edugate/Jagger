<?php
if (!defined('BASEPATH'))

    exit('No direct script access allowed');
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
 * Fedcategory Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
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
 * Fvalidatoredit Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Fvalidatoredit extends MY_Controller {

      public function __construct()
      {
          parent::__construct();
          $this->load->helper('form');
          $this->load->library('form_validation');
      }

      /**
       * @todo validate form
       */
      private function _submit_validate()
      {
          return FALSE;
      }
      public function vedit($fedid=NULL,$fvalidatorid=NULL)
      {
          $loggedin = $this->j_auth->logged_in();
          if(!$loggedin)
          {
              redirect('auth/login', 'location');
          }
          if(!ctype_digit($fedid))
          {
              show_error('not found',404);
          }
          $this->load->library('zacl');
          $federation = $this->em->getRepository("models\Federation")->findOneBy(array('id'=>$fedid));
          if(empty($federation))
          {
             show_error('fed not found',404);
          }
          $data['federationname'] = $federation->getName();
          $data['federationlink'] = base_url().'federations/manage/show/'.base64url_encode($federation->getName());
          
          if(!empty($fvalidatorid))
          {
             if(!ctype_digit($fvalidatorid))
             {
                  show_error('incorrect fvalidator id',404);
             }
             else
             {
                  $fvalidators = $federation->getValidators();
                  $fvalidator = $this->em->getRepository("models\FederationValidator")->findOneBy(array('id'=>$fvalidatorid));
           
                  if(empty($fvalidator) || !$fvalidators->contains($fvalidator))
                  {
                      show_error('fvalidator not found',404);
                  } 
              }
          }
          else
          {
             $data['newfvalidator'] = TRUE;
          }
          $group = "federation";
          $has_write_access = $this->zacl->check_acl('f_' . $federation->getId(), 'write', $group, '');
          
          if(!$has_write_access)
          {
              show_error('no perm',403);
          }
          if($this->_submit_validate()!==TRUE)
          {
             $data['content_view'] = 'manage/fvalidator_edit_view';
             $this->load->view('page',$data);
          }
          else
          {
              

          }
         
           
      }

}
