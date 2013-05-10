<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2012, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * Sync_metadata Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */


class Sync_metadata extends CI_Controller {
	protected $em;

	function __construct()
	{
		parent::__construct();
		$this->em = $this->doctrine->em;
		$this->load->library('curl');


	}

        public function metadataslist($i=null)
        {
              $this->output->set_content_type('text/plain');
              $baseurl = base_url();
               $result = array();
               if(empty($i))
               {
                  $federations = $this->em->getRepository("models\Federation")->findAll();
                  if(!empty($federations))
                  {
                     foreach($federations as $f)
                     {
                        $url = $baseurl . "metadata/federation/" . base64url_encode($f->getName()) . "/metadata.xml";
                        $result[]=array('group'=>'federation','name'=>base64url_encode($f->getName()),'url'=>$url);
                        if($f->getLocalExport() === TRUE)
                        {
                           $url = $baseurl . "metadata/federationexport/" . base64url_encode($f->getName()) . "/metadata.xml";
                           $result[]=array('group'=>'federationexport','name'=>base64url_encode($f->getName()),'url'=>$url);
                        }
                     }
                  }
                  $providers = $this->em->getRepository("models\Provider")->findAll();
                }
                else
                {
                  $providers = $this->em->getRepository("models\Provider")->findBy(array('entityid'=>base64url_decode($i)));
                }
               if(!empty($providers))
               {
                   foreach($providers as $p)
                   {
                      $url =$baseurl . "metadata/circle/" . base64url_encode($p->getEntityId()) . "/metadata.xml";
                      $result[] = array('group'=>'provider','name'=>base64url_encode($p->getEntityId()), 'url'=>$url);
                   }
               }
               $out = "";
               foreach($result as $r)
               {
                   $out .= $r['group'].";".$r['name'].";".$r['url']."\n";
               }

               $this->output->set_output($out);

                

        }

	/**
	 * $url - param is base64_encoded remote url where we want to get metadata from 
	 * $conditions is serialized array
	 * keys of $conditions:
	 *   'type' - what type of entitities to sync, possible values: all,idp,sp
	 *   'is_active' - imported entities should be set as active or inactive, possible boolean values: true, false
	 *   'is_local' - imported entities should be set as internal or external entities
	 *   'overwrite' - if imported entity already exists in database and it's set as local. if true then overwrite all values,
	 *        except is_active, is_local
	 *   'populate' - imported entity should be fully populated  - both static metadata and all values,
	 *        possible boolean values: true, false
	 *   'default_static' - if true then static metadata will be used for metadata generation, if you set as false, 
	 *        then you must set 'populate' as true 
	 */
	public function semiautomatic($syncpass,$encoded_url,$encoded_federationurn,$conditions_to_set=null)
	{

                $protectpass = $this->config->item('syncpass');
                if($protectpass != $syncpass)
                {
                     show_error('Access Denied - invalid token',403);
                     return;
                }
		$conditions_default = array(
			'type' => 'all',
			'is_active'=>true,
			'is_local'=>false,
			'overwrite'=>false,
			'populate'=>true,
			'default_static'=>true,
                        'removeexternal'=>false,

		);
//		$cli = $this->input->is_cli_request();
                
//		if(empty($cli))
//		{
//			show_error('Access Denied',403);
//			return;
//		}
		$conditions_in_array = array();
		if(!empty($conditions_to_set))
		{
			$conditions_in_array = unserialize(base64url_decode($conditions_to_set));
		}
		$conditions = array_merge($conditions_default,$conditions_in_array);

		//print_r($conditions);

		$url = base64url_decode($encoded_url);
		$federationurn = base64url_decode($encoded_federationurn);
		$tmp_feds = new models\Federations();
		$fed = $tmp_feds->getOneByUrn($federationurn);
		if(empty($fed))
		{
			show_error('Federation not found',404);
		}
		$metadata_body = $this->curl->simple_get($url);
		if(empty($metadata_body))
		{
			show_error('empty metadata',404);
		}
		$this->load->library(array('metadata_validator','curl','metadata2import'));
                $is_valid_metadata = $this->metadata_validator->validateWithSchema($metadata_body);
                if (empty($is_valid_metadata))
                {
			show_error('Metadata is not valid',500);
		}

		$type_of_entities = strtoupper($conditions['type']); 
		if($conditions['populate'])
		{
			$full = true;
		}
		else 
		{
			$full = false;
		}

		$defaults = array(
			'overwritelocal' => $conditions['overwrite'],
			'active'=> $conditions['is_active'],
			'static'=>$conditions['default_static'],
			'local'=> $conditions['is_local'],
			'federations'=>array($fed->getName()),
                        'removeexternal'=>$conditions['removeexternal'],

		);
		$other = null;
                $result = $this->metadata2import->import($metadata_body, $type_of_entities, $full, $defaults, $other);





	}


}
