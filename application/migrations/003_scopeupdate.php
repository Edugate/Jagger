<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_scopeupdate extends CI_Migration {

   function up() {
        $this->em = $this->doctrine->em;
        $providers = $this->em->getRepository("models\Provider")->findAll();
        foreach($providers as $p)
        {
           $p->convertScope();
           $this->em->persist($p);
        }
        $this->em->flush();
   }
   function down() {
       echo "down";
    }

}
