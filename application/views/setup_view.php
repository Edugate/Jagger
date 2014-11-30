<?php

$attributes = array('id'=>'formver2','class'=>'register');
$hidden = array('setupallowed'=>'dd');


echo '<div class="small-12 columns">';
echo form_open(base_url() . 'setup/submit',$attributes,$hidden);
echo form_fieldset('Administrator  details');
echo '<div class="small-12 columns">';
echo '<div class="medium-3 columns medium-text-right">'.form_label('Username', 'username').'</div>';
echo '<div class="medium-7 end columns">'.form_input('username',set_value('username')).form_error('username', '<span class="error">', '</span>').'</div>';
echo '</div>';

echo '<div class="small-12 columns">';
echo '<div class="medium-3 columns medium-text-right">'.form_label('email', 'email').'</div>';
echo '<div class="medium-7 end columns">'.form_input('email',set_value('email')). form_error('email', '<span class="error">', '</span>').'</div>';
echo '</div>';

echo '<div class="small-12 columns">';
echo '<div class="medium-3 columns medium-text-right">'.form_label('Password', 'password').'</div>';
echo '<div class="medium-7 end columns">'.form_password('password').form_error('password', '<span class="error">', '</span>').'</div>';
echo '</div>';

echo '<div class="small-12 columns">';
echo '<div class="medium-3 columns medium-text-right">'.form_label('Confirm password', 'passwordconf').'</div>';
echo '<div class="medium-7 end columns">'.form_password('passwordconf').form_error('passwordconf', '<span class="error">', '</span>').'</div>';
echo '</div>';

echo '<div class="small-12 columns">';
echo '<div class="medium-3 columns medium-text-right">'.form_label('First name', 'fname').'</div>';
echo '<div class="medium-7 end columns">'.form_input('fname').form_error('fname', '<span class="error">', '</span>').'</div>';
echo '</div>';

echo '<div class="small-12 columns">';
echo '<div class="medium-3 columns medium-text-right">'.form_label('Surname', 'sname').'</div>';
echo '<div class="medium-7 end columns">'.form_input('sname').form_error('sname', '<span class="error">', '</span>').'</div>';
echo '</div>';



echo form_fieldset_close();
echo '<div class="small-12 columns small-text-center">';
echo '<input type="submit" value="submit" name="submit" class="button">';

echo form_close();
echo '</div>';



echo '</div>';



if(!empty($message))
{
   echo $message;
}
