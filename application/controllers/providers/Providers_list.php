<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}


/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2016 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */
class Providers_list extends MY_Controller
{

    public function __construct() {
        parent::__construct();
        $this->output->set_content_type('application/json');
    }

    /**
     * @param $type
     * @return CI_Output
     */
    public function show($type) {

        if (!$this->input->is_ajax_request()) {
            return $this->output->set_status_header(403)->set_output('Request not allowed');
        }
        if ($type !== 'idp' && $type !== 'sp') {
            return $this->output->set_status_header(404)->set_output('Incorrect type of entities provided');
        }

        if (!$this->jauth->isLoggedIn()) {
            return $this->output->set_status_header(403)->set_output('Not authenticated - access denied');
        }
        $this->load->library('zacl');
        $resource = 'sp_list';
        if ($type === 'idp') {
            $resource = 'idp_list';
        }

        $action = 'read';
        $group = 'default';
        $hasReadAccess = $this->zacl->check_acl($resource, $action, $group, '');
        if (!$hasReadAccess) {
            return $this->output->set_status_header(403)->set_output('Permission denied');
        }

        $result = $this->getList($type);
        return $this->output->set_content_type('application/json')->set_output(json_encode($result));

    }

    private function getList($type, $fresh = null) {
        $lang = MY_Controller::getLang();
        $keyprefix = getCachePrefix();
        $this->load->driver('cache', array('adapter' => 'memcached', 'key_prefix' => $keyprefix));

        $cachedid = $type . '_l_' . $lang;
        $result['data'] = array();
        $result['baseurl'] = base_url();
        $result['statedefs'] = array(
            'plocked'    => array('1' => '' . lang('rr_locked') . ''),
            'pactive'    => array('0' => '' . lang('rr_disabled') . ''),
            'plocal'     => array('0' => '' . lang('rr_external') . ''),
            'pstatic'    => array('1' => '' . lang('rr_static') . ''),
            'pvisible'   => array('0' => '' . lang('lbl_publichidden') . ''),
            'pavailable' => array('0' => 'unavailable'),
        );
        $result['definitions']['lang'] = array(
            'next'        => lang('nextpage'),
            'previous'    => lang('prevpage'),
            'search'      => lang('rr_search'),
            'display'     => lang('displperpage'),
            'btnexternal' => lang('extprov'),
            'btnlocal'    => lang('localprov'),
        );

        if ($type === 'idp') {
            $lnamcol = lang('e_idpservicename');
        } else {
            $lnamcol = lang('e_spservicename');
        }

        $result['columns'] = array(
            'nameandentityid' => array('colname' => '' . $lnamcol . '', 'status' => 1, 'cols' => array('pname', 'pentityid')),
            'url'             => array('colname' => '' . lang('e_orgurl') . '', 'status' => 1, 'cols' => array('phelpurl')),
            'pregdate'        => array('colname' => '' . lang('tbl_title_regdate') . '', 'status' => 1, 'cols' => array('pregdate')),
            'contacts'        => array('colname' => 'Contacts', 'status' => 1, 'cols' => array('contacts')),
            'entstatus'       => array('colname' => 'status', 'status' => 1, 'cols' => array('plocked', 'pactive', 'pvisible', 'pstatic', 'plocal'))
        );

        $cachedResult = $this->cache->get($cachedid);
        if (empty($cachedResult) || $fresh !== null) {
            log_message('debug', 'list of ' . $type . '(s) for lang (' . $lang . ') not found in cache ... retriving from db');
            $tmpprovs = new models\Providers();
            $typeToUpper = strtoupper($type);
            /**
             * @var $list models\Provider[]
             */
            $list = $tmpprovs->getProvidersListPartialInfo($typeToUpper);
            $counter = 0;
            $data = array();
            foreach ($list as $v) {
                $cnts = array();
                foreach ($v->getContacts() as $c) {
                    $cnts[] = html_escape($c->getType() . ': ' . $c->getFullName() . ' <' . $c->getEmail() . '>');
                }
                $data['"' . $counter++ . '"'] = array(
                    'pid'        => $v->getId(),
                    'plocked'    => (int)$v->getLocked(),
                    'pactive'    => (int)$v->getActive(),
                    'plocal'     => (int)$v->getLocal(),
                    'pstatic'    => (int)$v->getStatic(),
                    'pvisible'   => (int)$v->getPublicVisible(),
                    'pavailable' => (int)$v->getAvailable(),
                    'pentityid'  => $v->getEntityId(),
                    'pname'      => $v->getNameToWebInLang($lang, $type),
                    'pregdate'   => $v->getRegistrationDateInFormat('Y-m-d', jauth::$timeOffset),
                    'phelpurl'   => $v->getHelpdeskUrl(),
                    'contacts'   => $cnts,
                );
            }
            if (count($data) > 0) {
                $this->cache->save($cachedid, $data, 7200);
            }
            $result['data'] = &$data;
        } else {
            $result['data'] = &$cachedResult;
        }

        return $result;
    }

}
