<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_nameidsprotosupdate extends CI_Migration {

   function up() {
        $this->em = $this->doctrine->em;
        $providers = $this->em->getRepository("models\Provider")->findAll();
        foreach($providers as $p)
        {
           $type = $p->getType();
           $protos = $p->getProtocol()->toArray();
           $nameids = $p->getNameIdToArray();
           
           if($type != 'SP')
           {
              $p->setProtocolSupport('idpsso',$protos);
              $p->setNameIds('idpsso',$nameids);
           }
           if($type != 'IDP')
           {
              $p->setProtocolSupport('spsso',$protos);
              $p->setNameIds('spsso',$nameids);

           }
        }
        $this->em->flush();
   }
   function down() {
       echo "down";
    }

}
