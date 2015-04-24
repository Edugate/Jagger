<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Migration_upconfigtitlehead extends CI_Migration {

	function up()
	{
		$this->em = $this->doctrine->em;
		$initData = array();

		$confelement = $this->em->getRepository("models\Preferences")->findOneBy(array('name'=>'titleheader'));
		if(empty($confelement))
		{
			$c = new models\Preferences();
			$c->setName('titleheader');
			$c->setCategory('page');
			$c->setDescname('show title/subtitle on page');
			$c->setDescription('option allows to show/hide title on the page');
			$c->setEnabled();
			$c->setType('bool');
			$c->setValue('');
			$this->em->persist($c);
		}
		else
		{
			$confelement->setCategory('page');
			$confelement->setType('bool');
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
