<?php

$errors_v = validation_errors('<span class="span-12">', '</span><br />');
if (!empty($errors_v))
{
    echo '<div data-alert class="small-12 medium-10 large-8 small-centered columns alert-box alert">';
    echo $errors_v;
    echo '</div>';
}
if (!empty($message))
{
    echo '<div data-alert class="small-12 medium-10 large-8 small-centered columns alert-box success">' . $message . '</div>';
}

$form_attributes = array('id' => 'formver2', 'class' => 'register');
$action = base_url() . "manage/users/passedit/" . $encoded_username;
echo form_open($action, $form_attributes);

if ($write_access && !$manage_access)
{
    echo '<div class="small-12 columns">';
    echo '<div class="small-3 columns">' . jform_label('Current password', 'oldpassword') . '</div>';
    echo '<div class="small-6 large-6 columns end">' . form_password('oldpassword') . '</div>';
    echo '</div>';
}
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">' . jform_label(lang('rr_npassword'), 'password') . '</div>';
echo '<div class="small-6 large-6 columns end">' . form_password('password') . '</div>';
echo '</div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">' . jform_label(lang('rr_npasswordconf'), 'passwordconf') . '</div>';
echo '<div class="small-6 large-6 columns end">' . form_password('passwordconf') . '</div>';
echo '</div>';
echo '<div class="buttons small-12 columns text-right">';
echo '<div class="small-9 columns "><button type="submit"  name="submit", value="submit" class="button savebutton saveicon">' . lang('rr_changepass') . '</button></div>';
echo '</div>';
echo form_close();