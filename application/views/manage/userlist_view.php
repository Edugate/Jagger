<?php
$form = '<form id="filter-form"><input name="filter" id="filter" value="" maxlength="30" size="30" type="text" placeholder="'.lang('rr_filter').'"></form>';
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

echo '<div class="medium-3 columns">'. $form.'</div>';
echo '<div class="medium-3 medium-offset-6 column end text-right"><a href="'.base_url('manage/users/add').'" class="button tiny" data-reveal-id="newusermodal">'.lang('btn_newuser').'</a></div>';
$tmpl = array ( 'table_open'  => '<table  id="details" class="userlist filterlist">' );

$this->table->set_template($tmpl);
$this->table->set_empty("&nbsp;"); 
$this->table->set_heading(''.lang('rr_username').'',''.lang('rr_userfullname').'',''.lang('rr_uemail').'',''.lang('lastlogin').'','ip');
echo '<div class="small-12 columns">';
echo $this->table->generate($userlist);
echo '</div>';
$this->table->clear();

echo '<div id="newusermodal" class="reveal-modal medium" data-reveal aria-labelledby="modalTitle" aria-hidden="true" role="dialog">';
echo '<h4 id="modalTitle">'.lang('rr_newuserform').'</h4>';
    $this->load->view('manage/new_user_view');
    ?>
    <a class="close-reveal-modal" aria-label="Close">&#215;</a>
</div>
