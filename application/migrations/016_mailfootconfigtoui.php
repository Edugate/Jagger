<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Migration_mailfootconfigtoui extends CI_Migration {

	function up()
	{
		$this->em = $this->doctrine->em;

		$confelement = $this->em->getRepository("models\Preferences")->findOneBy(array('name'=>'mailfooter'));
		if(empty($confelement))
		{
            $footerFromConfig = $this->config->item('mail_footer');
			$c = new models\Preferences();
			$c->setName('mailfooter');
			$c->setCategory('mail');
			$c->setDescname('mail signature');
			$c->setDescription('mail signature added to every notication');
			$c->setEnabled();
			$c->setType('text');
            if(empty($footerFromConfig))
            {
                $c->setValue('');
            }
            else
            {
                $c->setValue($footerFromConfig);
            }

			$this->em->persist($c);
		}
		else
		{
			$confelement->setCategory('mail');
			$confelement->setType('text');
			$this->em->persist($confelement);
		}

		try{
			$this->em->flush();
		}
		catch(Exception $e)
		{
			log_message('error',__METHOD__.' :: '. $e);
			return false;
		}


	}

	function down()
	{

	}


}
