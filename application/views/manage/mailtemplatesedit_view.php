<?php
$errors = validation_errors('<div>', '</div>');

if(!empty($errors))
{
    echo '<div class="small-12 columns">';
    echo '<div data-alert class="alert-box alert">';
    echo $errors;
    echo '</div>';
    echo '</div>';
}

echo form_open();
if ($newtmpl === TRUE)
{
    echo '<div class="small-12 columns">';
    echo jGenerateDropdown(lang('mtmplgroup'), 'msggroup', $groupdropdown, set_value('msggroup', ''), '');
    echo '</div>';
    echo '<div class="small-12 columns">';
    echo jGenerateDropdown(lang('rr_lang'), 'msglang', $langdropdown, '', '');
    echo '</div>';
}

echo '<div class="small-12 columns">';
echo jGenerateInput(lang('mtmplsbj'), 'msgsubj', set_value('msgsubj', $msgsubj), '', null);
echo '</div>';
echo '<div class="small-12 columns">';
echo jGenerateTextarea(lang('mtmplbody'), 'msgbody', set_value('msgbody', $msgbody), '');

echo '</div>';

echo form_error('mtmplsbj');

echo '<div class="small-12 columns text-right">';
echo '<div class="medium-10 columns end"><button type="submit">Save</button>';
echo '</div>';

echo form_close();

