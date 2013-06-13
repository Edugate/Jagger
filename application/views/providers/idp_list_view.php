<div id="subtitle"><h3>
<?php 
  if($typeidps === 'local')
  {
     echo lang('rr_tbltitle_listlocalidps');
  }
  elseif($typeidps === 'external')
  {
     echo lang('rr_tbltitle_listextidps');
  }
  else
  {
     echo lang('rr_tbltitle_listidps');

  }
  
 echo ' (' . lang('rr_found') . ' ' . $idps_count . ')';?></h3></div>
<?php
$form = '<form id="filter-form">'. lang('rr_filter') .': <input name="filter" id="filter" value="" maxlength="30" size="30" type="text"></form>';
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
echo $form;
$tmpl = array('table_open' => '<table  id="details" class="zebra drop-shadow lifted idplist">');

$this->table->set_template($tmpl);
$this->table->set_heading(lang('tbl_title_nameandentityid'),'#', lang('tbl_title_regdate'), lang('tbl_title_helpurl'));
echo $this->table->generate($idprows);
$this->table->clear();
?>


