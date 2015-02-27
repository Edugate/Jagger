<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Migration_addbreacrumbs extends CI_Migration {

	function up()
	{
		$this->em = $this->doctrine->em;
		$initData = array();

		$breadcrumbs = $this->em->getRepository("models\Preferences")->findOneBy(array('name'=>'breadcrumbs'));
		if(empty($breadcrumbs))
		{
			$c = new models\Preferences();
			$c->setName('breadcrumbs');
			$c->setCategory('page');
			$c->setDescname('show breadcrumbs');
			$c->setDescription('option allows to display breadcrumbs');
			$c->setDisabled();
			$c->setType('bool');
			$c->setValue('');
			$this->em->persist($c);
		}
		else
		{
			$breadcrumbs->setCategory('page');
			$breadcrumbs->setType('bool');
			$this->em->persist($breadcrumbs);
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
