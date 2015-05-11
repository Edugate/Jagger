<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_certificatesupdate extends CI_Migration {

   function up() {
        $this->em = $this->doctrine->em;
        $providers = $this->em->getRepository("models\Provider")->findAll();
        foreach($providers as $p)
        {
           $type = $p->getType();
           $cert = $p->getCertificates();
           foreach($cert as $c)
           {
               if($c->getType() == 'sso')
               {
                   if($type === 'IDP' || $type === 'BOTH')
                   { 
                      $c->setAsIDPSSO();
                   }
                   else
                   {
                      $c->setASSPSSO();
                   }
                   $this->em->persist($c);
               }
           }
        }
        $this->em->flush();
   }
   function down() {
       echo "down";
    }

}
