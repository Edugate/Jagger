<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @author    Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 * @copyright 2015 HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 *
 */

class Joinfed extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $loggedin = $this->jauth->isLoggedIn();
        if (!$loggedin) {
            redirect('auth/login', 'location');
        } else {
            $this->load->library('zacl');

        }
        $this->load->helper('form');
    }

    private function submitValidate()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('fedid', 'Federation', 'trim|numeric');
        $this->form_validation->set_rules('formmessage', 'Message', 'strip_tags|trim|required');
        return $this->form_validation->run();
    }

    public function joinfederation($providerid = null)
    {
        if (!ctype_digit($providerid)) {
            show_error(lang('error_incorrectprovid'), 404);
        }
        /**
         * @var models\Provider $ent
         */
        $ent = $this->em->getRepository("models\Provider")->findOneBy(array('id' => $providerid));
        if ($ent === null) {
            show_error(lang('rerror_provnotfound'), 404);
        }

        $hasWriteAccess = $this->zacl->check_acl($ent->getId(), 'write', 'entity');
        if (!$hasWriteAccess || $ent->getLocked() ) {
            show_error('Permission denied', 403);
        }
        $myLang = MY_Controller::getLang();
        $entType = strtolower($ent->getType());
        $nameInLang = $ent->getNameToWebInLang($myLang, $entType);
        $this->title = $nameInLang . ':' . lang('joinfederation');
        $data = array(
            'name' => $nameInLang,
            'entityid' => $ent->getEntityId(),
            'providerid' => $ent->getId(),
            'titlepage' => '<a href="' . base_url() . 'providers/detail/show/' . $ent->getId() . '">' . $nameInLang . '</a>',
            'subtitlepage' => lang('fedejoinform')
        );
        if (strcasecmp($entType, 'SP') == 0) {
            $plist = array('url' => base_url('providers/sp_list/showlist'), 'name' => lang('serviceproviders'));
        } else {
            $plist = array('url' => base_url('providers/idp_list/showlist'), 'name' => lang('identityproviders'));
        }
        $data['breadcrumbs'] = array(
            $plist,
            array('url' => base_url('providers/detail/show/' . $ent->getId() . ''), 'name' => '' . html_escape($nameInLang) . ''),
            array('url' => '#', 'name' => lang('fedejoinform'), 'type' => 'current'),
        );
        /**
         * @var models\Federation[] $allFederations
         */
        $allFederations = $this->em->getRepository("models\Federation")->findAll();
        $federations = $ent->getFederations();

        $availableFederations = array();
        foreach ($allFederations as $ff) {
            if (!$federations->contains($ff)) {
                $availableFederations[$ff->getId()] = $ff->getName();
            }
        }

        $feds_dropdown = $availableFederations;

        if ($this->submitValidate() === true) {
            $message = $this->input->post('formmessage');
            $fedid = $this->input->post('fedid');
            /**
             * @var $federation models\Federation
             */
            $federation = $this->em->getRepository("models\Federation")->findOneBy(array('id' => $fedid));
            if (empty($federation)) {
                show_error('' . lang('error_nofedyouwantjoin') . '', 404);
            }
            if (!$federations->contains($federation)) {
                /**
                 * @todo create queue
                 */

                $this->load->library('approval');
                $add_to_queue = $this->approval->invitationFederationToQueue($ent, $federation, 'Join', $message);
                if ($add_to_queue) {
                    $this->load->library('tracker');
                    $mail_sbj = "Request  to join federation: " . $federation->getName();


                    $providername = $nameInLang;
                    $providerentityid = $ent->getEntityId();
                    $awaitingurl = base_url() . 'reports/awaiting';
                    $fedname = $federation->getName();
                    if (empty($message)) {
                        $message = '';
                    }
                    $mail_body = '';
                    $this->tracker->save_track(strtolower($ent->getType()), 'request', $ent->getEntityId(), 'requested to join federation: ' . $federation->getName() . '. Message attached: ' . html_escape($message) . '', false);

                    $overrideconfig = $this->config->item('defaultmail');
                    if (!empty($overrideconfig) && is_array($overrideconfig) && array_key_exists('joinfed', $overrideconfig) && !empty($overrideconfig['joinfed'])) {
                        $b = $overrideconfig['joinfed'];
                    } else {
                        $b = "Hi," . PHP_EOL .
                            "Just few moments ago Administator of Provider %s (%s)" . PHP_EOL .
                            "sent request to Administrators of Federation: %s" . PHP_EOL .
                            "to access  him as new federation member." . PHP_EOL .
                            "To accept or reject this request please go to Resource Registry" . PHP_EOL .
                            "%s" . PHP_EOL . PHP_EOL . PHP_EOL . "======= additional message attached by requestor ===========" .
                            PHP_EOL . "%s" . PHP_EOL . "=============================================================" . PHP_EOL;
                    }
                    $localizedmail = $this->config->item('localizedmail');
                    if (!empty($localizedmail) && is_array($localizedmail) && array_key_exists('joinfed', $localizedmail) && !empty($localizedmail['joinfed'])) {
                        $c = $localizedmail['joinfed'];
                        $mail_body .= sprintf($c, $providername, $providerentityid, $fedname, $awaitingurl, $message);
                        $mail_body .= PHP_EOL.PHP_EOL . sprintf($b, $providername, $providerentityid, $fedname, $awaitingurl, $message);
                    } else {
                        $mail_body .= sprintf($b, $providername, $providerentityid, $fedname, $awaitingurl, $message);
                    }
                    $subscribers = $this->em->getRepository("models\NotificationList")->findBy(
                        array('type' => 'joinfedreq', 'federation' => $federation->getId(), 'is_enabled' => true, 'is_approved' => true));

                    foreach ($subscribers as $s) {
                        $m = new models\MailQueue();
                        $m->setSubject($mail_sbj);
                        $m->setBody($mail_body);
                        $m->setDeliveryType($s->getNotificationType());
                        $m->setRcptto($s->getRcpt());
                        $this->em->persist($m);
                    }
                    $this->emailsender->addToMailQueue(array('joinfedreq', 'gjoinfedreq'), $federation, $mail_sbj, $mail_body, array(), FALSE);
                    try {
                        $this->em->flush();
                        log_message('info', 'JAGGER: ' . __METHOD__ . ' ' . $this->session->userdata('user_id') . ': request to join federation: entityID: ' . $ent->getEntityId() . ', fed: ' . $federation->getName());
                    } catch (Exception $e) {
                        log_message('error', 'JAGGER: ' . $e);
                        show_error('Internal server error', 500);

                    }

                    $data['content_view'] = 'manage/joinfederation_view';
                    $data['success_message'] = lang('confirmreqsuccess');
                    return $this->load->view(MY_Controller::$page, $data);
                }


            }
        } else {
            $data['error_message'] = validation_errors('<div>', '</div>');
            if (count($feds_dropdown) > 0) {
                $n[''] = lang('selectfed');
                if (defined('SORT_NATURAL') && defined('SORT_FLAG_CASE')) {
                    asort($feds_dropdown, SORT_NATURAL | SORT_FLAG_CASE);
                } else {
                    natcasesort($feds_dropdown);
                }
                $feds_dropdown = $n + $feds_dropdown;

                $data['feds_dropdown'] =  $feds_dropdown;
                $data['showform'] = true;
                $data['content_view'] = 'manage/joinfederation_view';
                $this->load->view(MY_Controller::$page, $data);
            } else {
                $data['error_message'] = lang('cantjoinnonefound');
                $data['content_view'] = 'manage/joinfederation_view';
                $this->load->view(MY_Controller::$page, $data);

            }
        }
    }

}
