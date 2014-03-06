<div id="subtitle"><h3><?php echo lang('rr_userslist');?></h3></div>
<?php
$form = '<form id="filter-form">'. lang('rr_filter') .': <input name="filter" id="filter" value="" maxlength="30" size="30" type="text"></form>';
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
echo $form;
$tmpl = array ( 'table_open'  => '<table  id="details" class="userlist filterlist">' );

$this->table->set_template($tmpl);
$this->table->set_empty("&nbsp;"); 
$this->table->set_heading(''.lang('rr_username').'',''.lang('rr_userfullname').'',''.lang('rr_uemail').'',''.lang('lastlogin').'','ip');
echo $this->table->generate($userlist);
$this->table->clear();

