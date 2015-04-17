<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Spmatrix extends MY_Controller
{
    /**
     * @var $tmp_providers \models\Provider[]
     */
    private $tmp_providers;

    function __construct()
    {
        parent::__construct();
        $this->tmp_providers = new models\Providers;
    }

    public function show($id = null)
    {
        $loggedin = $this->j_auth->logged_in();
        if (!$loggedin) {
            redirect('auth/login', 'location');
        }
        if (empty($id) || !ctype_digit($id)) {
            show_404();
        }
        /**
         * @vat $sp models\Provider
         */
        $sp = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $id));
        if (empty($sp)) {
            show_404();
        }


        $this->load->library('zacl');

        $myLang = MY_Controller::getLang();
        $titlename = $sp->getNameToWebInLang($myLang, $sp->getType());
        $this->title = $titlename;


        $data = array('content_view'=>'reports/spmatrix_view','spid'=>$sp->getId());
        $this->load->view('page', $data);

    }

    public function getdiag($id = null)
    {
        $loggedin = $this->j_auth->logged_in();
        $isAjax = $this->input->is_ajax_request();
        if (!$loggedin) {
            set_status_header(403);
            echo 'no perm';
            return;
        }
        if (empty($id) || !ctype_digit($id)) {
            set_status_header(404);
            echo 'no found';
            return;
        }
        /**
         * @vat $sp models\Provider
         */
        $sp = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $id));
        if (empty($sp)) {
            set_status_header(404);
            echo 'no found';
            return;
        }

        $spEntityId = $sp->getEntityId();
        $this->load->library('zacl');

        $myLang = MY_Controller::getLang();
        $titlename = $sp->getNameToWebInLang($myLang, $sp->getType());
        $this->title = $titlename;
        $result['data'] = array();
        /**
         * @var $members models\Provider[]
         */
        $members = $this->tmp_providers->getCircleMembersIDP($sp, NULL, TRUE);

        $keyprefix = getCachePrefix();
        $this->load->driver('cache', array('adapter' => 'memcached', 'key_prefix' => $keyprefix));

        $this->load->library('arp_generator');

        $ok = false;
        foreach ($members as $member) {
            $cacheid = 'arp_' . $member->getId();
            $arpcached = $this->cache->get($cacheid);
            $entityID = $member->getEntityId();
            if(empty($arpcached)) {
                $result1 = $this->arp_generator->arpToXML($member, true);
            }
            else
            {
                $result1 = $arpcached;
            }
            $result1_keys = array_keys($result1);

            if (in_array($spEntityId, $result1_keys)) {
                if(!$ok) {
                    $result['attrs'] =  $result1[''.$spEntityId.'']['req'];
                    $ok = true;
                }
                $result['data'][] = array('idpid' => $member->getId(), 'name' => $member->getNameToWebInLang($myLang, 'idp'), 'entityid' => $entityID, 'data' => $result1['' . $spEntityId . '']);

            }

        }
        if(count($result['data'])==0)
        {
            $result['message'] = 'No policies found';
        }
        $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

}