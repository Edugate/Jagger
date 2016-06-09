<?php



$form_attributes = array('id' => 'formver2', 'class' => 'register');
$action = base_url() . "manage/users/add";
$form = form_open($action, $form_attributes);
$form .= '<div class="alert-box alert message hidden" data-alert></div>';
$form .= '<div class="small-12 columns">';
$form .= '<div class="medium-3 columns medium-text-right"><label for="username">'.lang('rr_username').'</label></div>';
$form .= '<div class="medium-6 large-7 columns end">' . form_input('username', set_value('username','',FALSE)) . '</div>';
$form .= '</div>';
$form .= '<div class="small-12 columns">';
$form .= '<div class="medium-3 columns medium-text-right"><label for="access">'.lang('rr_typeaccess').'</label></div>';
$access_type = array('' => '' . lang('rr_select') . '', 'local' => '' . lang('rr_onlylocalauthn') . '', 'fed' => '' . lang('rr_onlyfedauth') . '', 'both' => '' . lang('rr_bothauth') . '');
$form .= '<div class="medium-6 large-7 columns end">' . form_dropdown('access', $access_type, set_value('access'), 'class="nuseraccesstype"') . '</div>';
$form .= '</div>';
$form .= '<div class="small-12 columns">';
$form .= '<div class="medium-3 columns medium-text-right"><label for="email">'.lang('rr_uemail') .'</label></div>';
$form .= '<div class="medium-6 large-7 columns end">' . form_input('email', set_value('email','',FALSE)) . '</div>';
$form .= '</div>';
$form .= '<div class="small-12 columns passwordrow">';
$form .= '<div class="medium-3 columns medium-text-right"><label for="password">'.lang('rr_password').'</label></div>';
$form .= '<div class="medium-6 large-7 columns end">' . form_password('password') . '</div>';
$form .= '</div>';
$form .= '<div class="small-12 columns passwordrow">';
$form .= '<div class="medium-3 columns medium-text-right"><label for="passwordconf">'.lang('rr_passwordconf') .'</label></div>';
$form .= '<div class="medium-6 large-7 columns end">' . form_password('passwordconf') . '</div>';
$form .= '</div>';
$form .= '<div class="small-12 columns">';
$form .= '<div class="medium-3 columns medium-text-right"><label for="fname">'.lang('rr_fname').'</label></div>';
$form .= '<div class="medium-6 large-7 columns end">' . form_input('fname', set_value('fname','',FALSE)) . '</div>';
$form .= '</div>';
$form .= '<div class="small-12 columns">';
$form .= '<div class="medium-3 columns medium-text-right"><label for="sname">'.lang('rr_lname').'</label></div>';
$form .= '<div class="medium-6 large-7 columns end">' . form_input('sname', set_value('sname','',FALSE)) . '</div>';
$form .= '</div>';

$form .= '<div class="small-12 columns">';
$btns = array(
    '<a href="#" class="button small alert" data-close>'.lang('rr_cancel').'</a>',
    '<button type="submit"  name="submit" value="submit" class="button">' . lang('adduser_btn') . '</button>'
);
$form .= '<div class="medium-9 large-10 text-right columns">'.revealBtnsRow($btns).'</div>';
$form .='</div>';
$form .= form_close();
echo $form;

