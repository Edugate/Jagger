<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$tmpl = array('table_open' => '<table  id="details" class="zebra">');

$this->table->set_template($tmpl);
$this->table->set_heading(lang('tbl_title_nameandentityid'),lang('tbl_title_regdate'), lang('tbl_title_helpurl'));
$this->table->set_caption(lang('rr_tbltitle_listsps').' ('.lang('rr_found').' '.$sps_count.')');
echo $this->table->generate($sprows);
$this->table->clear();
?>
