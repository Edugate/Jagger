<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_descriptionmigration extends CI_Migration {

   function up() {
        $this->em = $this->doctrine->em;
        $types = array('IDP','SP','BOTH');

        foreach($types as $type)
        {
           $providers = $this->em->getRepository("models\Provider")->findBy(array('type'=>''.$type.'','is_static'=>false));
           foreach($providers  as $p)
           {
              $description = $p->getDescription();
              if(!empty($description))
              {
                 $description = trim($description);
              }
              if(empty($description))
              {
                 continue;
              }

              if($type === 'BOTH')
              {
                 $t = array('idp','sp');
              }
              else
              {
                 $t = array(''.strtolower($type).'');
              }
              foreach($t as $v)
              {
                  $ex = $p->getExtendMetadata();
                  $parent = null;
                  foreach ($ex as $e)
                  {
                     $extend['' . $e->getType() . '']['' . $e->getNamespace() . '']['' . $e->getElement() . ''][] = $e;
                     if ($e->getElement() == 'UIInfo' && $e->getNamespace() == 'mdui')
                     {
                         if ($e->getType() === $v)
                         {
                             $parent = $e;
                         }
                     }
                  }
                  $mduidescs = $p->getLocalDescriptionsToArray($v);
                  if(!isset($mduidescs['en']))
                  {
                       if(empty($parent))
                       {
                            $parent = new models\ExtendMetadata;
                            $parent->setType('sp');
                            $parent->setNamespace('mdui');
                            $parent->setElement('UIInfo');
                            $p->setExtendMetadata($parent);
                       }
                       $extdesc = new models\ExtendMetadata;
                       $extdesc->setNamespace('mdui');
                       $extdesc->setType($v);
                       $extdesc->setElement('Description');
                       $extdesc->setValue($description);
                       $extdesc->setAttributes(array('xml:lang'=>'en'));
                       $extdesc->setProvider($p);
                       $p->setExtendMetadata($extdesc);
                       $extdesc->setParent($parent);
                       $this->em->persist($parent);
                       $this->em->persist($extdesc);
                       $this->em->persist($p);
 
                  }
 
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

        }
        return TRUE;



   }
  
   function down() {
       echo "down";
    }

}
