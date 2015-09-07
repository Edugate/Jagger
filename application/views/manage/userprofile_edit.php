<?php

echo validation_errors('<div class="error">', '</div>');


echo form_open($formaction, array('autocomplete'=>'off'));
echo '
<div class="small-12 column">
    <div class="medium-3 column medium-text-right"><label>' . lang('rr_username') . '</label></div>
    <div class="medium-6 end column">' . form_input(array('name' => 'username', 'type' => 'text', 'readonly' => 'readonly', 'value' => html_escape($username))) . '</div>
</div>';
if($local) {
    if (!$isadmin) {
        echo '<div class="small-12 column">
  <div class="medium-3 column medium-text-right"><label for="currentpass">' . lang('rr_oldpassword') . '</label></div>
    <div class="medium-6 end column">' . form_input(array('name' => 'currentpass', 'type' => 'password', 'autocomplete' => 'off')) . '</div>
</div>';
    }
    echo '<div class="small-12 column">
  <div class="medium-3 column medium-text-right"><label for="npassword">' . lang('rr_npassword') . '</label></div>
    <div class="medium-6 end column">' . form_input(array('name' => 'newpassword', 'type' => 'password', 'value' => '', 'autocomplete' => 'off')) . '</div>
</div>';
    echo '<div class="small-12 column">
  <div class="medium-3 column medium-text-right"><label for="confirmnpassword">' . lang('rr_npasswordconf') . '</label></div>
    <div class="medium-6 end column">' . form_input(array('name' => 'confirmnpassword', 'type' => 'password', 'value' => '', 'autocomplete' => 'off')) . '</div>
</div>';
}
echo '<div class="small-12 column">
  <div class="medium-3 column medium-text-right"><label for="fname">' . lang('rr_fname') . '</label></div>
    <div class="medium-6 end column">' . form_input(array('name' => 'fname', 'type' => 'text', 'value' => set_value('fname', $fname), 'autocomplete' => 'off')) . '</div>
</div>';
echo '<div class="small-12 column">
  <div class="medium-3 column medium-text-right"><label for="sname">' . lang('rr_lname') . '</label></div>
    <div class="medium-6 end column">' . form_input(array('name' => 'sname', 'type' => 'text', 'value' => set_value('sname', $sname), 'autocomplete' => 'off')) . '</div>
</div>';

echo '<div class="small-12 column">
  <div class="medium-3 column medium-text-right"><label for="email">' . lang('rr_uemail') . '</label></div>
    <div class="medium-6 end column">' . form_input(array('name' => 'email', 'type' => 'text', 'value' => set_value('email', $email), 'autocomplete' => 'off')) . '</div>
</div>';

echo '<div class="small-12 column">
  <div class="medium-3 column medium-text-right"><label for="confirmemail">' . lang('rr_confirmuemail') . '</label></div>
    <div class="medium-6 end column">' . form_input(array('name' => 'confirmemail', 'type' => 'text', 'value' => set_value('confirmemail'), 'autocomplete' => 'off')) . '</div>
</div>';
if ($isadmin) {
    echo '<div class="small-12 column">
  <div class="medium-3 column medium-text-right"><label for="accesstype[]">' . lang('rr_typeaccess') . '</label></div>
    <div class="medium-6 end column">' . form_checkbox(array('name' => 'accesstype[]', 'value' => 'fed', 'checked' => $federated)) . '<label>Federated</label>
      ' . form_checkbox(array('name' => 'accesstype[]', 'value' => 'local', 'checked' => $local)) . '<label>Local authentication</label> </div>
</div>';

    echo '<div class="small-12 column">
  <div class="medium-3 column medium-text-right"><label for="accessroles[]">Access Roles</label></div>
    <div class="medium-6 end column">';
    foreach ($roles as $key => $value) {
        echo form_checkbox(array('name' => 'accessroles[]', 'value' => '' . $key . '', 'checked' => $value)) . '<label>' . $key . '</label>';
    }
    echo '</div>
</div>';

}
if ($show2fa === true) {
    echo '<div class="small-12 column">
  <div class="medium-3 column medium-text-right"><label for="secondfactor">2ndFactor</label></div>
    <div class="medium-6 end column">';
    if (count($allowed2fengines) > 0) {
        $allowed2fengines = array('none'=>'none') + $allowed2fengines;
        if($user2factor === null)
        {
            $user2factor = 'none';
        }
        foreach ($allowed2fengines as $fengine) {

            $fchecked = false;
            if ($fengine === $user2factor) {
                $fchecked = true;
            }
            echo form_radio(array('name' => 'secondf[]', 'value' => '' . $fengine . '', 'checked' => $fchecked)) . '<label>' . $fengine . '</label>';
        }
    }elseif($user2factor !==null)
    {
        echo form_radio(array('name' => 'secondf[]', 'value' => 'none' )) . '<label>none</label>';
        echo form_radio(array('name' => 'secondf[]', 'value' => ''.html_escape($user2factor).'','disabled'=>'disabled', 'checked'=>'checked' )) . '<label>'.html_escape($user2factor).'</label>';
    }
    else {
    echo 'Not available';
    }
    echo '</div></div>';
}

$btns = array(
        '<a href="'.$userprofileurl.'" class="button alert">' . lang('rr_cancel') . '</a>',
        '<button type="submit" name="update" value="updateprofile" class="button">' . lang('btnupdate') . '</button>'
    );
  echo '<div class="small-9 end column">';


echo revealBtnsRow($btns);

echo '</div>';
echo form_close();
