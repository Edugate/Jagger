
<?php

$errors = validation_errors('<div>', '</div>');

if (!empty($errors))
{
    echo '<div  data-alert class="alert-box alert ">' . $errors . '</div>';
}

$form_attributes = array('id' => 'formver2', 'class' => 'register');
$action = base_url() . "manage/users/add";
$form = form_open($action, $form_attributes);
$form .= '<div class="small-12 columns">';
$form .= '<div class="small-3 columns">' . jform_label('' . lang('rr_username') . '', 'username') . '</div>';
$form .= '<div class="small-6 large-7 columns end">' . form_input('username', set_value('username','',FALSE)) . '</div>';
$form .= '</div>';

$form .= '<div class="small-12 columns">';
$form .= '<div class="small-3 columns">' . jform_label('' . lang('rr_uemail') . '', 'email') . '</div>';
$form .= '<div class="small-6 large-7 columns end">' . form_input('email', set_value('email','',FALSE)) . '</div>';
$form .= '</div>';
$form .= '<div class="small-12 columns passwordrow">';
$form .= '<div class="small-3 columns">' . jform_label('' . lang('rr_password') . '', 'password') . '</div>';
$form .= '<div class="small-6 large-7 columns end">' . form_password('password') . '</div>';
$form .= '</div>';
$form .= '<div class="small-12 columns passwordrow">';
$form .= '<div class="small-3 columns">' . jform_label('' . lang('rr_passwordconf') . '', 'passwordconf') . '</div>';
$form .= '<div class="small-6 large-7 columns end">' . form_password('passwordconf') . '</div>';
$form .= '</div>';
$form .= '<div class="small-12 columns">';
$form .= '<div class="small-3 columns">' . jform_label('' . lang('rr_fname') . '', 'fname') . '</div>';
$form .= '<div class="small-6 large-7 columns end">' . form_input('fname', set_value('fname','',FALSE)) . '</div>';
$form .= '</div>';
$form .= '<div class="small-12 columns">';
$form .= '<div class="small-3 columns">' . jform_label('' . lang('rr_lname') . '', 'sname') . '</div>';
$form .= '<div class="small-6 large-7 columns end">' . form_input('sname', set_value('sname','',FALSE)) . '</div>';
$form .= '</div>';
$form .= '<div class="small-12 columns">';
$form .= '<div class="small-3 columns">' . jform_label('' . lang('rr_typeaccess') . '', 'access') . '</div>';
$access_type = array('' => '' . lang('rr_select') . '', 'local' => '' . lang('rr_onlylocalauthn') . '', 'fed' => '' . lang('rr_onlyfedauth') . '', 'both' => '' . lang('rr_bothauth') . '');
$form .= '<div class="small-6 large-7 columns end">' . form_dropdown('access', $access_type, set_value('access'), 'class="nuseraccesstype"') . '</div>';
$form .= '</div>';
$form .= '<div class="small-12 columns">';
$form .= '<div class="small-9 large-10 text-right columns"><button type="submit"  name="submit" value="submit" class="addbutton addicon">' . lang('adduser_btn') . '</button></div>';
$form .='</div>';
$form .= form_close();
echo $form;
