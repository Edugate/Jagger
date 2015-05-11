<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_attributesupdate extends CI_Migration {

   function up() {
        $this->em = $this->doctrine->em;
        $attrtmp = $this->em->getRepository("models\Attribute")->findAll();
        $attributes = array();
        foreach($attrtmp as $a)
        {
           
           $attributes[''.$a->getName().''] = $a->getOid();
        }
        $newattrs = array(
           array('n'=>'manager','f'=>'manager','oid'=>'urn:oid:0.9.2342.19200300.100.1.10','urn'=>'urn:mace:dir:attribute-def:manager','desc'=>'The Manager attribute type specifies the manager'),
           array('n'=>'cn','f'=>'CommonName','oid'=>'urn:oid:2.5.4.3','urn'=>'urn:mace:dir:attribute-def:cn','desc'=>'CommonName'),
           array('n'=>'l','f'=>'locality name','oid'=>'urn:oid:2.5.4.7','urn'=>'urn:mace:dir:attribute-def:l','desc'=>'The Locality Name attribute type specifies a locality. When used as a component of a directory name, it identifies a geographical area or locality in which the named object is physically located or with which it is associated in some other important way'),
           array('n'=>'st','f'=>'State/Province Name','oid'=>'urn:oid:2.5.4.8','urn'=>'urn:mace:dir:attribute-def:st','desc'=>'The State Or Province Name attribute type specifies a state or province. When used as a component of a directory name, it identifies a geographical subdivision in which the named object is physically located or with which it is associated in some other important way'),
           array('n'=>'street','f'=>'Street','oid'=>'urn:oid:2.5.4.9','urn'=>'urn:mace:dir:attribute-def:st','desc'=>'The street Address attribute type specifies a site for the local distribution and physical delivery in a postal address, i.e., the street name, place, avenue, and the house number.'),
           array('n'=>'title','f'=>'Title','oid'=>'urn:oid:2.5.4.12','urn'=>'urn:mace:dir:attribute-def:title','desc'=>'The Title attribute type specifies the designated position or function'),
           array('n'=>'description','f'=>'Description','oid'=>'urn:oid:2.5.4.13','urn'=>'urn:mace:dir:attribute-def:description','desc'=>'The description'),
           array('n'=>'businessCategory','f'=>'Business Category','oid'=>'urn:oid:2.5.4.15','urn'=>'urn:mace:dir:attribute-def:businessCategory','desc'=>'This attribute provides the facility to interrogate the Directory about people sharing the same occupation'),
           array('n'=>'postalAddress','f'=>'postalAddress','oid'=>'urn:oid:2.5.4.16','urn'=>'urn:mace:dir:attribute-def:postalAddress','desc'=>'postalAddress'),
           array('n'=>'postalCode','f'=>'Postal Code','oid'=>'urn:oid:2.5.4.17','urn'=>'urn:mace:dir:attribute-def:postalCode','desc'=>'postalCode'),
           array('n'=>'postOfficeBox','f'=>'Post Office Box','oid'=>'urn:oid:2.5.4.18','urn'=>'urn:mace:dir:attribute-def:postOfficeBox','desc'=>'postOfficeBox'),
           array('n'=>'physicalDeliveryOfficeName','f'=>'Physical Delivery Office Name','oid'=>'urn:oid:2.5.4.19','urn'=>'urn:mace:dir:attribute-def:physicalDeliveryOfficeName','desc'=>'It specifies the name of the city, village, etc. where a physical delivery office is situated'),
           array('n'=>'facsimileTelephoneNumber','f'=>'facsimileTelephoneNumber','oid'=>'urn:oid:2.5.4.23','urn'=>'urn:mace:dir:attribute-def:facsimileTelephoneNumber','desc'=>'The Facsimile Telephone Number attribute type specifies a telephone number for a facsimile terminal (and optionally its parameters)'),
           array('n'=>'seeAlso','f'=>'See also','oid'=>'urn:oid:2.5.4.34','urn'=>'urn:mace:dir:attribute-def:seeAlso','desc'=>'The See Also attribute specifies names of other Directory objects which may be other aspects (in some sense) of the same real world object.'),
           array('n'=>'userCertificate','f'=>'userCertificate','oid'=>'urn:oid:2.5.4.36','urn'=>'urn:mace:dir:attribute-def:userCertificate','desc'=>'userCertificate'),
           array('n'=>'initials','f'=>'Initials','oid'=>'urn:oid:2.5.4.43','urn'=>'urn:mace:dir:attribute-def:initials','desc'=>'The initials attribute type contains the initials of some or all of an individual\'s names, but not the surname(s).'),
        );


       foreach($newattrs as $n)
       {
           if(!in_array($n['oid'],$attributes))
           {
               $na = new models\Attribute;
               $na->setName($n['n']);
               $na->setFullname($n['f']);
               $na->setOid($n['oid']);
               $na->setUrn($n['urn']);
               $na->setDescription($n['desc']);
               $this->em->persist($na);

           }
 
       }
       $this->em->flush();
   }
  
   function down() {
       echo "down";
    }

}
