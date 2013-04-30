<?php 
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

?>
<div id="pagetitle"><?php echo lang('rr_arpoverview');?></div>
<?php


if(!empty($entityid))
{
   echo '<div id="subtitle"><h3>';
   echo lang('identityprovider').': '.anchor(''.base_url().'providers/detail/show/'.$idpid,$idpname) ;
   echo '</h3><h4>'.$entityid.'</h4></div>';
}
if(!empty($error_message))
{
  echo '<div class="alert">'.$error_message.'</div>';
}

if(!empty($excluded) && is_array($excluded) && count($excluded)>0)
{
   if(!empty($has_write_access))
   {
        $editlink = '<span class="lbl lbl-disabled"><a href="'.base_url().'manage/arpsexcl/idp/'.$idpid.'">'.lang('rr_editarpexc').'</a></span>';
   }
   else
   {
        $editlink = '';
   }

   echo '<div id="excarpslist"><b>'.lang('rr_arpexclist_title').'</b> '.$editlink;
   echo '<ol>';
   foreach($excluded as $v)
   {
       echo '<li>'.$v.'</li>';
   }
   echo '</ol></div>';
}

if(!empty($result))
{
   $tmpl = array('table_open' => '<table  id="idpmatrix" class="zebra">');
   $this->table->set_template($tmpl);
   $this->table->set_empty('&nbsp;');
   echo   $this->table->generate($result);
   $this->table->clear();
}
