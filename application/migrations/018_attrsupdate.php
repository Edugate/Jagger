<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_attrsupdate extends CI_Migration {

   function up() {
        $this->em = $this->doctrine->em;
       /**
        * @var models\Attribute[] $attrtmp
        */
        $attrtmp = $this->em->getRepository("models\Attribute")->findAll();
        foreach ($attrtmp as $attr){
            $name = $attrtmp->getName();
            if($name === 'transientId' || $name === 'persistentId'){
                $attr->setShowInmetadata(false);
                $this->em->persis($attr);
            }
        }

       $this->em->flush();
   }
  
   function down() {
       echo "down";
    }

}
