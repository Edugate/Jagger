<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_entcatsupdate extends CI_Migration {

   function up() {
        $this->em = $this->doctrine->em;
        $cocs = $this->em->getRepository("models\Coc")->findAll();
        foreach($cocs as $k=>$v)
        {
           $curtype = $v->getType();
           if(empty($curtype))
           {
              $v->setType('entcat');
              $this->em->persist($v);
           }

        }
        try{
          $this->em->flush();
          return True;
        }
        catch(Exception $e)
        {
           log_message('error',__METHOD__.' '.$e);
           return FALSE;
        }

   }
  
   function down() {
       echo "down";
    }

}
