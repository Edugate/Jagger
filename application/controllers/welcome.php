<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Welcome extends MY_Controller
{

    /**
     * Index Page for this controller.
     *
     * Maps to the following URL
     * 		http://example.com/index.php/welcome
     * 	- or -  
     * 		http://example.com/index.php/welcome/index
     * 	- or -
     * Since this controller is set as the default controller in 
     * config/routes.php, it's displayed at http://example.com/
     *
     * So any other public methods not prefixed with an underscore will
     * map to /index.php/welcome/<method_name>
     * @see http://codeigniter.com/user_guide/general/urls.html
     */
    public function index()
    {
        $user = new models\User;
        $user->setUsername('josephwynn');
        $user->setPassword('Passw0rd');
        $user->setEmail('wildlyinaccurate@gmail.com');

        $this->load->view('welcome_message', array('user' => $user));
    }

    public function add_sp_from_queue()
    {
        $qid = '4';
        $queue = $this->em->find('models\Queue', $qid);

        $sp1 = new models\Provider;
        $sp1 = $queue->getData();
        echo "<h1>" . $sp1->getName() . "</h1>";
        echo "<pre>";
        var_dump($sp1);
        echo "<pre>";
        $this->em->persist($sp1);
        $this->em->remove($queue);
        $this->em->flush();
    }

    public function add_sp()
    {
        $contact1 = new models\Contact;
        $contact1->setFullname('Joe7Bloggs');
        $contact1->setEmail('joe@example.co');
        $contact1->setPhone('+3531121212');
        $contact1->setType('Administrative');
        //$this->em->persist($contact1);

        $contact2 = new models\Contact;
        $contact2->setFullname('Joe8 Bloggs');
        $contact2->setEmail('joe@example.co');
        $contact2->setPhone('+3531121212');
        $contact2->setType('Administrative');
        //$this->em->persist($contact2);

        $sp = new models\Provider;
        $sp->setName('test SP7');
        $sp->setType('SP');
        $sp->setEntity('https://sp7.example.com/shibboleth');
        $sp->setHelpdeskUrl('http://4example.com');
        $sp->setHomeUrl('http://4example.com');
        $sp->setNameid('1');
        $sp->setValidFrom();
        $sp->setValidTo();
        $sp->setDefaultState();
        $sp->getContacts()->add($contact1);
        $sp->getContacts()->add($contact2);
        echo "<pre>";
        var_dump($sp);
        echo "####################################################################\n";
        echo "</pre>";
        $contact1->setProvider($sp);
        $contact2->setProvider($sp);
        //$this->em->persist($sp);

        $queue1 = new models\Queue;
        $queue1->addSP($sp);
        $queue1->setAction('Create');
        $queue1->setName('new SP');
        $this->em->persist($queue1);
        echo "<pre>";
        var_dump($queue);
        echo "</pre>
                     ////////////////////////////////////////////////////////";
        echo "<pre>";
        echo "</pre>";
        $this->em->flush();
    }

    public function del_sp()
    {
        $id = 6;

        $sp = $this->em->find('models\Provider', $id);
        $sp->setHomeUrl('http://example2.com');
        $this->em->remove($sp);
        $this->em->flush();

        echo "<pre>";
        var_dump($sp);
        echo "</pre>";
    }

    public function modify_sp($id)
    {


        $sp = $this->em->find('models\Provider', $id);
        $sp->setHomeUrl('http://examplei.com');
        $this->em->persist($sp);
        $this->em->flush();

        echo "<pre>";
        //var_dump($sp);
        echo "</pre>";
    }

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */
