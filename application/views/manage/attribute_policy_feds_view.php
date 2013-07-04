<?php

if (!empty($message))
{
    echo '<span class="message">' . $message . '</span>';
}
if (!empty($error))
{
    echo '<span class="error">' . $error . '</span>';
}


$attributes = array('class' => 'span-16', 'id' => 'formver1');
$hidden = array('idpid' => $idpid);
$target = base_url() . 'manage/attribute_policy/show_feds/'.$idpid;
echo '<div id="pagetitle">'.lang('mngtarpforfed').'</div>';
echo '<div id="subtitle"><h3><a href="'.base_url().'roviders/detail/show/'.$idpid.'">'.$idpname.'</a></h3></div>';

    echo form_open($target, $attributes, $hidden);
    echo form_fieldset(''.lang('mngtarpforfed').'');
    echo '<ol><li>';
    echo form_label(lang('selectfed'), 'fedid') ;
    echo $federations;
    echo '</li></ol>';
    echo form_fieldset_close();
    echo '<div class="buttons"><button type="submit" name="submit" id="submit" value="submit" class="btn positive"><span class="save">'.lang('nextstep').'</span></button></div>';
    echo form_close();

