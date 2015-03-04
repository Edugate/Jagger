<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Migration_fixregpoliciestrim extends CI_Migration
{

	function up()
	{
		$this->em = $this->doctrine->em;
		$types = array('entcat');

		foreach ($types as $type) {

			$res1 = array();
			$res2 = array();
			$dataCollection = $this->em->getRepository("models\Coc")->findBy(array('type' => '' . $type . ''));
			foreach ($dataCollection as $v) {
				$res1[$v->getLang()][$v->getId()] = trim($v->getUrl());
				$res2[$v->getId()] = $v;
			}
			$duplicates = array();
			foreach ($res1 as $lang => $collection) {
				$duplicates[$lang] = array_unique(array_diff_assoc($collection, array_unique($collection)));

			}
			$p = array();
			foreach ($duplicates as $klang => $v) {
				if (count($v) > 0) {
					foreach ($v as $key => $value) {
						$p[$klang][] = array_keys($res1[$klang], $value);
					}
				}
			}
			$providersToPersist = array();
			if (count($p) > 0) {
				foreach ($p as $langkey => $col) {
					if (is_array($col) and count($col) > 1) {
						foreach ($col as $kcol => $vcol) {
							$elemIdToLeave = array_shift($vcol);
							$objToLeave = $res2[$elemIdToLeave];
							$objToLeave->setUrl(trim($objToLeave->getUrl()));

							if (is_array($vcol) && count($vcol) > 0) {
								foreach ($vcol as $vids) {
									$objToMerge = $res2[$vids];
									if (!empty($objToMerge)) {
										$isAvailable = $objToMerge->getAvailable();
										if ($isAvailable) {
											$objToLeave->setAvailable(TRUE);

										}
										$providers = $objToMerge->getProviders();
										foreach ($providers as $provider) {
											$providersToPersist[] = $provider;
											$provider->removeCoc($objToMerge);
											$provider->setCoc($objToLeave);
										}
										$this->em->remove($objToMerge);
									}
								}
							}
							$this->em->persist($objToLeave);

						}
					}

				}
			}
			foreach ($providersToPersist as $pr) {
				$this->em->persist($pr);
			}

		}
		try {
			$this->em->flush();
			
		} catch (Exception $e) {
			log_message('error', __METHOD__ . ' :: ' . $e);
			return false;
		}


	}

	function down()
	{

	}


}
