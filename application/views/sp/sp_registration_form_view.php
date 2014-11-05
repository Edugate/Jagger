<?php
$this->load->helper("cert");
$errors_v = validation_errors('<span>', '</span><br />');
if (!empty($errors_v))
{
    echo '<div class="error">' . $errors_v . '</div>';
}

$form_attributes = array('id' => 'multistepform', 'class' => 'register');
$action = current_url();
echo form_open($action, $form_attributes);
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';
echo jform_label('Metadata <small>(' . lang('rr_optional') . ')</small>' . showBubbleHelp(lang('rhelp_regspparsemeta')), 'metadatabody');
echo '</div>';
echo '<div class="small-6 large-7 columns">';
echo form_textarea(array(
    'id' => 'metadatabody',
    'name' => 'metadatabody',
    'value' => set_value('metadatabody'),
    'cols' => 65,
    'rows' => 20,
    'style' => 'font-family: monospace; font-size: smaller'
));
echo '</div>';

echo '<div class="small-3 large-2 columns"><button  type="button" name="parsemetadatasp" id="parsemetadatasp" value="parsemetadatasp" class="savebuttoni tiny">' . lang('btnparsemeta') . '</button>';
echo '</div>';
echo '</div>';

echo '<button type="submit" name="next" class="advancedmode button" value="' . base_url() . 'providers/sp_registration/advanced">' . lang('nextstep') . '</button>';
echo form_close();
?>





