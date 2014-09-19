<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_filldefaulteattrentcat extends CI_Migration {

   function up() {
        $this->em = $this->doctrine->em;
        $entcats = $this->em->getRepository("models\Coc")->findBy(array('type'=>'entcat'));

        foreach($entcats as $e)
        {
           $subtype = $e->getSubtype();
           if(empty($subtype))
           {
              $e->setSubtype('http://macedir.org/entity-category');
              $this->em->persist($e);
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
