<?php

echo '<div id="subtitle">' . lang('rr_removing') . ' ' . $entityid . ' ' . $link . '</div>';
$errors_v = validation_errors('<span class="span-12">', '</span><br />');
if (!empty($error_message) || !empty($errors_v))
{
    echo '<div class="alert">';
    if (!empty($error_message))
    {
        echo $error_message . '<br />';
    }
    if (!empty($errors_v))
    {
        echo $errors_v;
    }
    echo '</div>';
}

if (!empty($success_message))
{
    echo '<div class="success">' . $success_message . '</div>';
}
if ($showform)
{
    $this->load->helper('form');
    $attributes = array('class' => 'span-16', 'id' => 'formver1');
    echo form_open(current_url(), $attributes);
    echo form_fieldset(lang('rr_provider_rmform'));
    echo '<ol>';
    echo '<li>';
    $in = array('name' => 'entity', 'id' => 'entity');
    echo form_label(lang('rr_plsenterentityid'), 'entity');
    echo form_input($in);
    echo '</li>';
    echo '</ol>';
    echo form_fieldset_close();
    echo '<div class="buttons"><button name="submit" type="submit" id="submit" value="Remove" class="btn positive"><span class="save">' . lang('rr_btn_rmprovider') . '</span></button></div>';
    echo form_close();
}
