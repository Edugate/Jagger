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
 * Setup Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class Setup extends MY_Controller {

    protected $em;
    protected $member_role;

    function __construct() {
        parent::__construct();
        $this->em = $this->doctrine->em;
        

        $setup_allowed = $this->config->item('rr_setup_allowed');
        if (!$setup_allowed === TRUE) {
            show_error('Setup is disabled', 404);
        }
        $this->member_role = null;
        $this->title = 'Setup';
        $this->load->library('form_validation');
    }

    public function index() {

        $this->title = 'JAGGER Setup';
        $data['titlepage'] = 'JAGGER (Federation management tool) Setup'; 
        $this->load->helper('form');
        $data['content_view'] = 'setup_view';
        $this->load->view('page',$data);
    }
    private function _submit_validate()
    {
        $this->form_validation->set_rules('setupallowed','Setup','setup_allowed');
        $this->form_validation->set_rules('username','Username','required|min_length[5]|max_length[128]|user_username_unique[username]');
        $this->form_validation->set_rules('email','E-mail','required|min_length[5]|max_length[128]|user_mail_unique[email]');
        $this->form_validation->set_rules('password','Password','required|min_length[5]|max_length[23]|matches[passwordconf]');
        $this->form_validation->set_rules('passwordconf','Password Confirmation','required|min_length[5]|max_length[23]');
        $this->form_validation->set_rules('fname','First name','required|min_length[3]|max_length[255]');
        $this->form_validation->set_rules('sname','Surname','required|min_length[3]|max_length[255]');

        
        return $this->form_validation->run();
 
    }
    public function submit() {
        if($this->_submit_validate())
        {
        $username = $this->input->post('username');
        $email = $this->input->post('email');
        $password = $this->input->post('password');
        $fname = $this->input->post('fname');
        $sname = $this->input->post('sname');
        /**
         * add user, system roles, and add user to Administrator role
         */
        $this->_populateFirstUser($username, $email, $password,$fname,$sname);
        /**
         * populate attributes
         */
        $this->_populateAttributes();
        $this->_populateResources();
        $this->em->flush();
        $data['content_view'] = 'setup_view';
        $data['message'] = 'Done! Don\'t forget to disable "setup allowed" in config file';
        $this->load->view('page',$data);
        }
        else
        {
            return $this->index();
        }
    }

    public function attributes() {
        $this->_populateAttributes();
        $this->em->flush();
    }


    private function _populateResources() {
        $resources = array(
            array('name' => 'default', 'parent' => '', 'default' => 'none'),
            array('name' => 'importer', 'parent' => 'default', 'default' => 'none'),
            array('name' => 'sp_list', 'parent' => 'default', 'default' => 'read'),
            array('name' => 'idp_list', 'parent' => 'default', 'default' => 'read'),
            array('name' => 'dashboard', 'parent' => 'default', 'default' => 'read'),
            array('name' => 'federation', 'parent' => 'default', 'default' => 'read'),
            array('name' => 'entity', 'parent' => 'default', 'default' => 'read'),
            array('name' => 'idp', 'parent' => 'entity', 'default' => 'read'),
            array('name' => 'sp', 'parent' => 'entity', 'default' => 'read'),
            array('name' => 'user', 'parent' => 'default', 'default' => 'read'),
            array('name' => 'password', 'parent' => 'user', 'default' => 'none'),
        );
        $parents = array();
        foreach ($resources as $r) {
            $r_name = $r['name'];
            $parent_name = $r['parent'];
            if (empty($parent_name)) {
                $res = new models\AclResource;
                $res->setResource($r['name']);
                $res->setDefaultValue($r['default']);
                $parents[$r['name']] = $res;
            } else {

                $res = new models\AclResource;
                $res->setResource($r['name']);
                $res->setDefaultValue($r['default']);
                $res->setParent($parents[$r['parent']]);
                $parents[$r['name']] = $res;
            }
            $this->em->persist($res);
            if($r_name == 'dashboard' || $r_name == 'sp_list' || $r_name == 'idp_list' || $r_name == 'entity')
            {
                $acl = new models\Acl;
                $acl->setResource($res);
                $acl->setRole($this->member_role);
                $acl->setAction('read');
                $acl->setAccess(true);
                $this->em->persist($acl);
            }

        }
    }

    private function _populateFirstUser($username, $email, $password, $fname, $sname) {

        $guest_role = new models\AclRole;
        $guest_role->setName('Guest');
        $guest_role->setDescription('role with lowest permissions');
        $guest_role->setType('system');
        $this->em->persist($guest_role);

        $user_role = new models\AclRole;
        $user_role->setName('Member');
        $user_role->setDescription('role with middle permissions');
        $user_role->setParent($guest_role);
        $user_role->setType('system');
        $this->em->persist($user_role);
        $this->member_role = $user_role;

        $admin_role = new models\AclRole;
        $admin_role->setName('Administrator');
        $admin_role->setDescription('role with highest permissions, only resource registry admins may be members of this group');
        $admin_role->setParent($user_role);
        $admin_role->setType('system');
        $this->em->persist($admin_role);

        $user = $this->em->getRepository("models\User")->findOneBy(array('username' => $username));
        if (empty($user)) {
            $user = new models\User;
        }
        $user->setSalt();
        $user->setUsername($username);
        $user->setPassword($password);
        $user->setEmail($email);
        $user->setGivenname($fname);
        $user->setSurname($sname);
        $user->setLocalEnabled();
        $user->setFederatedDisabled();
        $user->setAccepted();
        $user->setEnabled();
        $user->setValid();
        $admin_role->setMember($user);
        $this->em->persist($user);
        return true;
    }
    
    private function _populateAttributes() {
        $attributes = array(
            array('name' => 'preferredLanguage', 'fullname' => 'Preferred Language', 'oid' => 'urn:oid:2.16.840.1.113730.3.1.39', 'urn' => 'urn:mace:dir:attribute-def:preferredLanguage', 'description' => 'Preferred language: Users preferred language (see RFC1766)'),
            array('name' => 'email', 'fullname' => 'Email', 'oid' => 'urn:oid:0.9.2342.19200300.100.1.3', 'urn' => 'urn:mace:dir:attribute-def:mail', 'description' => 'E-Mail: Preferred address for e-mail to be sent to this person'),
            array('name' => 'homePostalAddress', 'fullname' => 'Home postal address', 'oid' => 'urn:oid:0.9.2342.19200300.100.1.39', 'urn' => 'urn:mace:dir:attribute-def:homePostalAddress', 'description' => 'Home postal address: Home address of the user'),
            array('name' => 'postalAddress', 'fullname' => 'Business postal address', 'oid' => 'urn:oid:2.5.4.16', 'urn' => 'urn:mace:dir:attribute-def:postalAddress', 'description' => 'Business postal address: Campus or office address'),
            array('name' => 'homePhone', 'fullname' => 'Private phone number', 'oid' => 'urn:oid:0.9.2342.19200300.100.1.20', 'urn' => 'urn:mace:dir:attribute-def:homePhone', 'description' => 'Private phone number'),
            array('name' => 'telephoneNumber', 'fullname' => 'Business phone number', 'oid' => 'urn:oid:2.5.4.20', 'urn' => 'urn:mace:dir:attribute-def:telephoneNumber', 'description' => 'Business phone number: Office or campus phone number'),
            array('name' => 'mobile', 'fullname' => 'Mobile phone number', 'oid' => 'urn:oid:0.9.2342.19200300.100.1.41', 'urn' => 'urn:mace:dir:attribute-def:mobile', 'description' => 'Mobile phone number'),
            array('name' => 'eduPersonAffiliation', 'fullname' => 'Affiliation', 'oid' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.1', 'urn' => 'urn:mace:dir:attribute-def:eduPersonAffiliation', 'description' => 'Affiliation: Type of affiliation with Home Organization'),
            array('name' => 'eduPersonOrgDN', 'fullname' => 'Organization path', 'oid' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.3', 'urn' => 'urn:mace:dir:attribute-def:eduPersonOrgDN', 'description' => 'Organization path: The distinguished name (DN) of the directory entry representing the organization with which the person is associated'),
            array('name' => 'eduPersonOrgUnitDN', 'fullname' => 'Organizational unit path', 'oid' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.4', 'urn' => 'urn:mace:dir:attribute-def:eduPersonOrgUnitDN', 'description' => 'Organization unit path: The distinguished name (DN) of the directory entries representing the person\'s Organizational Unit(s)'),
            array('name' => 'eduPersonEntitlement', 'fullname' => 'Entitlement', 'oid' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.7', 'urn' => 'urn:mace:dir:attribute-def:eduPersonEntitlement', 'description' => 'Member of: URI (either URL or URN) that indicates a set of rights to specific resources based on an agreement ac'),
            array('name' => 'surname', 'fullname' => 'Surname', 'oid' => 'urn:oid:2.5.4.4', 'urn' => 'urn:mace:dir:attribute-def:sn', 'description' => 'Surname or family name'),
            array('name' => 'givenName', 'fullname' => 'Given name', 'oid' => 'urn:oid:2.5.4.42', 'urn' => 'urn:mace:dir:attribute-def:givenName', 'description' => 'Given name of a person'),
            array('name' => 'uid', 'fullname' => 'User ID', 'oid' => 'urn:oid:0.9.2342.19200300.100.1.1', 'urn' => 'urn:mace:dir:attribute-def:uid', 'description' => 'A unique identifier for a person, mainly used for user identification within the user\'s home organization.'),
            array('name' => 'employeeNumber', 'fullname' => 'Employee number', 'oid' => 'urn:oid:2.16.840.1.113730.3.1.3', 'urn' => 'urn:mace:dir:attribute-def:employeeNumber', 'description' => 'Identifies an employee within an organization'),
            array('name' => 'ou', 'fullname' => 'Organizational Unit', 'oid' => 'urn:oid:2.5.4.11', 'urn' => 'urn:mace:dir:attribute-def:ou', 'description' => 'OrganizationalUnit currently used for faculty membership of staff at UZH.'),
            array('name' => 'eduPersonPrincipalName', 'fullname' => 'Principal Name', 'oid' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.6', 'urn' => 'urn:mace:dir:attribute-def:eduPersonPrincipalName', 'description' => 'eduPerson per Internet2 and EDUCAUSE see http://www.nmi-edit.org/eduPerson/draft-internet2-mace'),
            array('name' => 'eduPersonAssurance', 'fullname' => 'Assurance Level', 'oid' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.11', 'urn' => 'urn:mace:dir:attribute-def:assurance', 'description' => 'Level that describes the confidences that one can have into the asserted identity of the user.'),
            array('name' => 'transientId', 'fullname' => 'transient nameid for backward compatibility', 'oid' => 'urn:oid:1.2.3.4.5.6.7.8.9.10', 'urn' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient', 'description' => 'The Shibboleth transient ID is a name format that was used to encode eduPersonTargetedID in the past. A limited number of resources outside the Edugate federation still require this format'),
            array('name' => 'organizationName', 'fullname' => 'Organization Name', 'oid' => 'urn:oid:2.5.4.10', 'urn' => 'urn:mace:dir:attribute-def:o', 'description' => NULL),
            array('name' => 'CustomTestAttr', 'fullname' => 'Custom Test Attribute', 'oid' => 'urn:oid:1.2.3.4.5.6.7.8.9', 'urn' => 'urn:mace:dir:heanet.ie:attribute-def:customattr', 'description' => 'This attribute used by HEAnet to test custom attributes'),
            array('name' => 'eduPersonTargetedID', 'fullname' => 'eduPerson Targeted ID', 'oid' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.10', 'urn' => 'urn:mace:dir:attribute-def:eduPersonTargetedID', 'description' => 'A pseudonomynous ID generated by the IdP that is unique to each SP'),
            array('name' => 'persistentUID', 'fullname' => 'persistentUID', 'oid' => 'urn:oid:3.6.1.4.1.5923.1.1.1.10', 'urn' => 'urn:mace:eduserv.org.uk:athens:attribute-def:person:1.0:persistentUID', 'description' => 'This is the Athens persistentUID, it has no OID so we re-use the EduPerson PersistenID OID as it is closest'),
            array('name' => 'eduPersonScopedAffiliation', 'fullname' => 'Affiliation (Scoped)', 'oid' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.9', 'urn' => 'urn:mace:dir:attribute-def:eduPersonScopedAffiliation ', 'description' => 'the affiliation of the user to the organisation concatendated with the domain name of the org (e.g. staff@dcu.ie)'),
            array('name' => 'persistentId', 'fullname' => 'persistent nameid', 'oid' => 'urn:oid:1.2.3.4.5.6.7.8.9.11', 'urn' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent', 'description' => 'This attribute will appear in the subject section of AuthnRespones, only to be used if the service cannot handle a persistent ID within the attribute section of the AuthnResponse'),
            array('name' => 'freebusyurl', 'fullname' => 'freebusyurl', 'oid' => 'urn:oid:1.3.6.1.4.1.250.1.57', 'urn' => 'urn:mace:heanet.ie:attributedef:freebusyurl', 'description' => 'freebusyurl is a url to a user calendar in caldav format'),
            array('name' => 'sAMAccountName', 'fullname' => 'sAMAccountName', 'oid' => 'urn:oid:1.2.840.113556.1.4.221', 'urn' => 'urn:oid:1.2.840.113556.1.4.221', 'description' => 'sAMAccountName from Active Directory')
        );

        $i = 0;
        foreach ($attributes as $attr) {
            $at[$i] = new models\Attribute;
            $at[$i]->setName($attr['name']);
            $at[$i]->setFullname($attr['fullname']);
            $at[$i]->setOid($attr['oid']);
            $at[$i]->setUrn($attr['urn']);
            $at[$i]->setDescription($attr['description']);
            $at[$i]->setShowInmetadata(TRUE);
            $i++;
        }
        foreach ($at as $key) {
            $this->em->persist($key);
        }
        return true;
    }

}
