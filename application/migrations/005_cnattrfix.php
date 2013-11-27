<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_attributesupdate extends CI_Migration {

   function up() {
        $this->em = $this->doctrine->em;
        $attr = $this->em->getRepository("models\Attribute")->findOneBy(array('oid'=>'urn:oid:2.5.4.3'));
        if(!empty($attr))
        {
           $attr->setName('commonName');
           $this->em->persist($attr);
           $this->em->flush();

        }
        return True;

   }
  
   function down() {
       echo "down";
    }

}
