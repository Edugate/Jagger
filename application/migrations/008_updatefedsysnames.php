<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_updatefedsysnames extends CI_Migration {

   function up() {
        $this->em = $this->doctrine->em;
        $feds = $this->em->getRepository("models\Federation")->findAll();

        foreach($feds as $f)
        {
           $s = $f->getName();
           $senc = base64url_encode($s);
           $sysname = $f->getSysname();
           if(empty($sysname))
           {
              $f->setSysname($senc);
              $this->em->persist($f);
           }
        }


           
           try{
              $this->em->flush();
              
           }
           catch(Exception $e)
           {
              log_message('error',__METHOD__.' '.$e);
              return FALSE;
           }

        return TRUE;



   }
  
   function down() {
       echo "down";
    }

}
