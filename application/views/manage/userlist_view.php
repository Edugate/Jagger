<?php
$form = '<form id="filter-form"><input name="filter" id="filter" value="" maxlength="30" size="30" type="text" placeholder="'.lang('rr_filter').'"></form>';
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

echo '<div class="medium-3 columns">'. $form.'</div>';
echo '<div class="medium-3 medium-offset-6 column end text-right"><a href="'.base_url('manage/users/add').'" class="button tiny">'.lang('btn_newuser').'</a></div>';
$tmpl = array ( 'table_open'  => '<table  id="details" class="userlist filterlist">' );

$this->table->set_template($tmpl);
$this->table->set_empty("&nbsp;"); 
$this->table->set_heading(''.lang('rr_username').'',''.lang('rr_userfullname').'',''.lang('rr_uemail').'',''.lang('lastlogin').'','ip');
echo '<div class="small-12 columns">';
echo $this->table->generate($userlist);
echo '</div>';
$this->table->clear();

