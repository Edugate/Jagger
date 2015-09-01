<?php


echo form_open();
echo '
<div class="small-12 column">
    <div class="medium-3 column medium-text-right"><label>' . lang('rr_username') . '</label></div>
    <div class="medium-6 end column">'.form_input(array('name'=>'username','type'=>'text','readonly'=>'readonly','value'=>html_escape($username))).'</div>
</div>';
if (!$isadmin) {
    echo '<div class="small-12 column">
  <div class="medium-3 column medium-text-right"><label for="currentpass">' . lang('rr_oldpassword') . '</label></div>
    <div class="medium-6 end column">' . form_input(array('name'=>'currentpass','type'=>'password','autocomplete'=>'off')) . '</div>
</div>';
}
  echo '<div class="small-12 column">
  <div class="medium-3 column medium-text-right"><label for="npassword">' . lang('rr_npassword') . '</label></div>
    <div class="medium-6 end column">' . form_input(array('name'=>'npassword','type'=>'password','value'=>'','autocomplete'=>'off')) . '</div>
</div>';
echo '<div class="small-12 column">
  <div class="medium-3 column medium-text-right"><label for="confirmnpassword">' . lang('rr_npasswordconf') . '</label></div>
    <div class="medium-6 end column">' . form_input(array('name'=>'confirmnpassword','type'=>'password','value'=>'','autocomplete'=>'off')) . '</div>
</div>';

echo '<div class="small-12 column">
  <div class="medium-3 column medium-text-right"><label for="fname">' . lang('rr_fname') . '</label></div>
    <div class="medium-6 end column">' . form_input(array('name'=>'fname','type'=>'text','value'=>set_value('fname',$fname),'autocomplete'=>'off')) . '</div>
</div>';
echo '<div class="small-12 column">
  <div class="medium-3 column medium-text-right"><label for="sname">' . lang('rr_lname') . '</label></div>
    <div class="medium-6 end column">' . form_input(array('name'=>'sname','type'=>'text','value'=>set_value('sname',$sname),'autocomplete'=>'off')) . '</div>
</div>';

echo '<div class="small-12 column">
  <div class="medium-3 column medium-text-right"><label for="email">' . lang('rr_uemail') . '</label></div>
    <div class="medium-6 end column">' . form_input(array('name'=>'email','type'=>'text','value'=>set_value('email',$email),'autocomplete'=>'off')) . '</div>
</div>';

echo '<div class="small-12 column">
  <div class="medium-3 column medium-text-right"><label for="confirmemail">' . lang('rr_confirmuemail') . '</label></div>
    <div class="medium-6 end column">' . form_input(array('name'=>'confirmemail','type'=>'text','value'=>set_value('confirmemail'),'autocomplete'=>'off')) . '</div>
</div>';
if($isadmin)
{
    echo '<div class="small-12 column">
  <div class="medium-3 column medium-text-right"><label for="accesstype">' . lang('rr_typeaccess') . '</label></div>
    <div class="medium-6 end column">' . form_input(array('name'=>'confirmemail','type'=>'text','value'=>set_value('confirmemail'),'autocomplete'=>'off')) . '</div>
</div>';
}

echo form_close();