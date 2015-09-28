<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2015 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Oidcauth extends MY_Controller
{
    private $oidcEnabled;
    private $oidcOps;
    public function __construct() {
        parent::__construct();
        $this->oidcEnabled = $this->config->item('oidc_enabled');
        $this->oidcOps = $this->config->item('oidc_ops');
    }

    public function authn(){

        try{
            $this->checkGlobal();
        }
        catch(Exception $e){
            return $this->output->set_status_header(403)->set_output($e->getMessage());
        }
        if($this->j_auth->logged_in()){
             return $this->output->set_status_header(403)->set_output('Already authenticated');
        }
        if(!$this->input->is_ajax_request()){
            return $this->output->set_status_header(403)->set_output('Method not allowed');
        }

        $op = $this->input->post('op',true);
        if(strlen($op) && array_key_exists($op,$this->oidcOps)){
            $provider = $this->oidcOps[$op];
            $client = new Jagger\oidc\Client();
            $client->addScope($provider['scopes']);
            $client->setProviderURL($op);
            $client->setClientID($provider['client_id']);
            $client->setClientSecret($provider['client_secret']);
            $client->setRedirectURL(base_url('oidcauth/callback'));
            $client->setStateSession();
            $z = $client->generateAuthzRequest();
            return $this->output->set_header('application/json')->set_status_header(200)->set_output(json_encode(array('redirect'=>$z)));
        }
        else{
            return $this->output->set_status_header(403)->set_output('Missing');
        }


    }

    public function callback(){
        try{
            $this->checkGlobal();
        }
        catch(Exception $e){
            echo $e->getMessage();
        }
        if($this->j_auth->logged_in()){
            return $this->output->set_status_header(403)->set_output('Already authenticated');
        }
    }

    private function checkGlobal(){
        if($this->oidcEnabled !== true || !is_array($this->oidcOps) || count($this->oidcOps) == 0){
            throw new Exception('OpenID Connect not enabled');
        }
    }

}