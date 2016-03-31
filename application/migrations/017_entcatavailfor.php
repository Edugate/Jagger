<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}


class Migration_entcatavailfor extends CI_Migration
{
    /**
     * @var $em Doctrine\ORM\EntityManager
     */
    protected $em;

    function up() {
        $this->em = $this->doctrine->em;
        /**
         * @var models\Coc[] $entcats
         */
        $entcats = $this->em->getRepository('models\Coc')->findBy(array('type'=>'entcat'));

        foreach ($entcats as $entcat){
            $availFor = $entcat->getAvailFor();
            if($availFor === null){
                $subtype = $entcat->getSubtype();
                if($subtype === 'http://macedir.org/entity-category-support'){
                    $entcat->setAvailFor('idp');
                }
                else {
                    $entcat->setAvailFor('sp');
                }
                $this->em->persist($entcat);
            }
        }
        try {
            $this->em->flush();
        }
        catch (Exception $e){
            log_message('error',__METHOD__.' :: '. $e);
			return false;
        }
        return true;
    }

    function down(){}
    
}
