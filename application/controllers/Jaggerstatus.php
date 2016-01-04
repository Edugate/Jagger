<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');


/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2016 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Jaggerstatus extends MY_Controller
{
    public function __construct() {
        parent::__construct();
    }

    public function gstatus(){
        try {
            $this->em->getRepository('models\Provider')->findOneBy(array('id'=>'1'));
        }
        catch(Exception $e){
            log_message('error',__METHOD__.' '.$e);
            return $this->output->set_status_header(500)->_display('error');
        }
        return $this->output->set_status_header(200)->_display('OK');
    }

}