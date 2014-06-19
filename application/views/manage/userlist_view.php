<?php
$form = '<form id="filter-form"><input name="filter" id="filter" value="" maxlength="30" size="30" type="text" placeholder="'.lang('rr_search').'"></form>';
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
echo '<div class="small-3 small-offset-9 columns end">'. $form.'</div>';
$tmpl = array ( 'table_open'  => '<table  id="details" class="userlist filterlist">' );

$this->table->set_template($tmpl);
$this->table->set_empty("&nbsp;"); 
$this->table->set_heading(''.lang('rr_username').'',''.lang('rr_userfullname').'',''.lang('rr_uemail').'',''.lang('lastlogin').'','ip');
echo '<div class="small-12 columns">';
echo $this->table->generate($userlist);
echo '</div>';
$this->table->clear();

