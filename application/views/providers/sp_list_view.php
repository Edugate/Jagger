<div id="subtitle"><h3><?php echo lang('rr_tbltitle_listsps').' ('.lang('rr_found').' '.$sps_count.')' ;?></h3></div>

<?php
$form = '<div class="mobilehidden"><form id="filter-form">'. lang('rr_filter') .': <input name="filter" id="filter" value="" maxlength="30" size="30" type="text"></form></div>';
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
echo $form;
$tmpl = array('table_open' => '<table  id="details" class="zebra splist">');

$this->table->set_template($tmpl);
$this->table->set_heading(lang('tbl_title_nameandentityid'),'#',lang('tbl_title_regdate'), lang('tbl_title_helpurl'));
#$this->table->set_caption(lang('rr_tbltitle_listsps').' ('.lang('rr_found').' '.$sps_count.')');
echo $this->table->generate($sprows);
$this->table->clear();
?>
