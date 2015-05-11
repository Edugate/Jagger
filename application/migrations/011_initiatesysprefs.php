<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Migration_initiatesysprefs extends CI_Migration {

	function up()
	{
		$this->em = $this->doctrine->em;
		$initData = array();

		$cookieConsent = $this->em->getRepository("models\Preferences")->findOneBy(array('name'=>'cookieConsent'));
		if(empty($cookieConsent))
		{
			$c = new models\Preferences();
			$c->setName('cookieConsent');
			$c->setCategory('general');
			$c->setDescname('show cookie consent');
			$c->setDescription('option allows to display consent cookie on the top of page');
			$c->setDisabled();
			$c->setType('text');
			$c->setValue('Company uses cookies to your browsing experience and to create a secure and effective website for our customers. By using this site you agree that we may temporary store and access cookies on your devices, unless you have disabled your cookies');
			$this->em->persist($c);
		}
		else
		{
			$cookieConsent->setCategory('general');
			$cookieConsent->setType('text');
			$this->em->persist($cookieConsent);
		}

		$pageFooter = $this->em->getRepository("models\Preferences")->findOneBy(array('name'=>'pageFooter'));
		if(!empty($pageFooter))
		{
			$pageFooter->setCategory('page');
			$pageFooter->setType('text');
		}
		else
		{
			$pageFooter = new models\Preferences();
			$pageFooter->setName('pageFooter');
			$pageFooter->setDescname('Text on the bootom page');
			$pageFooter->setDescription('Footer  - Allow to add additional text on the bottom page');
			$pageFooter->setCategory('page');
			$pageFooter->setType('text');
			$configPageFooter= $this->config->item('pageFooter');
			if(!empty($configPageFooter))
			{
				$pageFooter->setEnabled();
				$pageFooter->setValue(''.$configPageFooter.'');

			}
			else
			{
				$pageFooter->setDisabled();
				$pageFooter->setValue('Powered by Jagger');
			}
		}
		$this->em->persist($pageFooter);


		$pageTitlePref =  $this->em->getRepository("models\Preferences")->findOneBy(array('name'=>'pageTitlePref'));
		if(!empty($pageTitlePref))
		{
			$pageTitlePref->setCategory('page');
			$pageTitlePref->setType('text');
		}
		else
		{
			$pageTitlePref = new models\Preferences();
			$pageTitlePref->setName('pageTitlePref');
			$pageTitlePref->setDescription('Text added as prefix into header title on a page');
			$pageTitlePref->setDescname('Header title prefix');
			$pageTitlePref->setCategory('page');
			$pageTitlePref->setType('text');

			$configTitlePref = $this->config->item('pageTitlePref');
			if(!empty($configTitlePref))
			{
				$pageTitlePref->setEnabled();
				$pageTitlePref->setValue(''.$configTitlePref.'');
			}
			else
			{
				$pageTitlePref->setDisabled();
				$pageTitlePref->setValue('Jagger :: ');
			}
		}
		$this->em->persist($pageTitlePref);


		$user2fset =  $this->em->getRepository("models\Preferences")->findOneBy(array('name'=>'user2fset'));
		if(empty($user2fset)) {
			$s = new models\Preferences();
			$s->setName('user2fset');
			$s->setDescription('If enabled then any user can enable/disable 2F authentication otherwise only Administrator can do it');
			$s->setDescname('Allow ordinary user set 2F');
			$s->setCategory('authn');
			$s->setType('bool');
			$s->setDisabled();
			$s->setValue('');
			$this->em->persist($s);
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
