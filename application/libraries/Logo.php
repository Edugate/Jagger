<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
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
 * Logo Class
 * 
 * @package     RR3
 * @subpackage  Libraries
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */
class Logo {

    var $logo_basepath = null;
    var $logo_baseurl = null;
    function __construct() {
        $this->ci = & get_instance();
        $this->ci->load->library('doctrine');
        $this->em = $this->ci->doctrine->em;
        $this->logo_basepath = FCPATH.$this->ci->config->item('rr_logouriprefix');
        $this->logo_baseurl =  $this->ci->config->item('rr_logobaseurl');
        if(empty($this->logo_baseurl))
        {
           $this->logo_baseurl = base_url().'logos/';
        }
         $this->ci->load->helper('form');
        
    }


    function getImageFiles()
    {
        //return $this->logo_basepath;
        $handle = opendir($this->logo_basepath);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $imagestable = array();
        while($file = readdir($handle))
        {
             if($file !== '.' && $file !== '..')
             {
                   $filetype = finfo_file($finfo,$this->logo_basepath . $file);
                   if($filetype == 'image/jpeg' or $filetype == 'image/png')
                   {
                       $size = getimagesize($this->logo_basepath . $file);
                       $imagestable[] = array(
                                     'name'=>$file,
                                     'width'=>$size[0],
                                     'height'=>$size[1],
                                     'mime'=>$size['mime'],
                                    );
                   }
             }
        }
        finfo_close($finfo); 
        return $imagestable;
    }
    function displayCurrentInGridForm(models\Provider $provider, $etype)
    {
        $result = null;
        
        $existing_logos = $this->em->getRepository("models\ExtendMetadata")->findBy(array('provider'=>$provider->getId(),'etype'=>$etype,'namespace'=>'mdui','element'=>'Logo'));
        $count_existing_logos = count($existing_logos);
        log_message('debug','no of logos for entity:'.$provider->getEntityId().' is '.$count_existing_logos);
        if($count_existing_logos > 0)
        {   
            $table_curr_images = array();
            foreach($existing_logos as $ex)
            {
               if (!(preg_match_all("#(^|\s|\()((http(s?)://)|(www\.))(\w+[^\s\)\<]+)#i", $ex->getEvalue(), $matches)))
               {
                    $ElementValue = $this->logo_baseurl . $ex->getEvalue();
               }
               else
               {
                    $ElementValue = $ex->getEvalue();
               }

        
               $cell = '<img src="'.$ElementValue.'" /><br />';
               $radio_data = array('id'=>'logoid','name'=>'logoid','value'=>$ex->getId(), 'checked'=>FALSE);
               $cell .= form_radio($radio_data).'<br />';
               $size = $ex->getAttributes();
               $size_str = '';
               if(is_array($size))
               {
                   foreach($size as $skey=>$svalue)
                   {
                       $size_str .= $skey.':'.$svalue.'<br />';
                   }
               }
               if(!empty($size_str))
               {
                  $size_str = "Size set in metadata<br />" . $size_str;
               }
               $cell .= "<span class=\"imginfo\">".$size_str."</span>";
               $table_curr_images[] = $cell;
            }
            $tables_style=array('table_open'  => '<table  id="details" class="zebra">');
            $ctable = $this->ci->table->set_template($tables_style);
            $ctable = $this->ci->table->set_caption('existing logos');
            if($count_existing_logos % 3)
            {
               $columns = 2;
               if($count_existing_logos % 2)
               {
                  $columns = 1;
               }
            }
            else
            {
               $columns = 3;
            }
            $ctable = $this->ci->table->make_columns($table_curr_images,$columns);
            $result = $this->ci->table->generate($ctable);
            $this->ci->table->clear();


        }
            return $result;
        


    }
    function displayAvailableInGridForm($attrname="filename",$columns=2)
    {
         $images = $this->getImageFiles();
         $no_images =  count($images);
         $table_images = array();
         $this->ci->load->library('table');
         $this->ci->load->helper('form');
         if(empty($attrname))
         {
            $attrname = "filename";
         } 
         if($no_images == 0)
         {
             return null;
         }
         foreach($images as $img)
         {
           $cell =  '<img src="'.$this->logo_baseurl . $img['name'].'" style="max-width: 150px"/><br />';
           $cell .= 'filename: '.$img['name'].'<br />';
           $cell .= 'size: '.$img['width'].'x'.$img['height'].'<br />';
           $cell .= '<input type="radio" name="'.$attrname.'" id="'.$attrname.'" value="'.$img['name'].'_size_'.$img['width'].'x'.$img['height'].'">' ;
           $table_images[] = $cell;
         }

         $tables_style=array('table_open'  => '<table  id="details" class="zebra" style="width: 100%">');
         $ntable = $this->ci->table->set_template($tables_style);
         $ntable = $this->ci->table->set_caption('Available logos');
         $ntable = $this->ci->table->make_columns($table_images,$columns);
         $result = $this->ci->table->generate($ntable);
         $this->ci->table->clear();
         return $result;
         
         
    }


}
