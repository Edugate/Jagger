<?php
if(!empty($error_message))
{
   echo '<div data-alert class="alert-box alert">'.$error_message.'</div>';
}
if(!empty($providers))
{
echo form_open();
    $errors_v = validation_errors('<div>', '</div>');
    if (!empty($errors_v)) {
        echo '<div data-alert class="alert-box alert">';
        echo $errors_v;
        echo "</div>";
    }
echo form_fieldset(lang('rr_fedinvidpsp').': '.$fedname);

echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">'.jform_label(''.lang('rrprovs').'','provider').'</div>';
echo '<div class="small-6 large-7 columns end">'.form_dropdown('provider',$providers,set_value('provider')).'</div>';
echo "</div>";
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">'.jform_label(''.lang('rr_message').'','message').'</div>';
echo '<div class="small-6 large-7 columns end">'.form_textarea('message').'</div>';
echo "</div>";
echo form_fieldset_close();
?>
<div class="buttons">
 <button type="submit" name="submit" value="Invitation" class="savebutton saveicon">
    <?php echo lang('sendinvbtn');?>
 </button>
</div>
<?php
echo form_close();
}
