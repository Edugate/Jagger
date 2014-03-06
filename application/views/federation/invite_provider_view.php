<?php
if(!empty($subtitle))
{
   echo '<div id="subtitle"><h3>'.$subtitle.'</h3></div>';
}
if(!empty($error_message))
{
   echo '<div class="alert span-24"><span>'.$error_message.'</span></div>';
}
if(!empty($providers))
{
echo form_open();
    $errors_v = validation_errors('<span class="span-12">', '</span><br />');
    if (!empty($errors_v)) {
        echo '<div class="error">';
        echo $errors_v;
        echo "</div>";
    }
echo form_fieldset(lang('rr_fedinvidpsp').': '.$fedname);
echo "<ol>";

echo "<li>";
echo form_label(''.lang('rrprovs').'','provider');
echo form_dropdown('provider',$providers,set_value('provider'));
echo "</li>";
echo "<li>";
echo form_label(''.lang('rr_message').'','message');
echo form_textarea('message');
echo "</li>";
echo "</ol>";
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
