<?php
if(!empty($subtitle))
{
   echo '<div id="subtitle" class="span-24">'.$subtitle.'</div>';
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
echo form_fieldset('Invite provider to join federation: '.$fedname);
echo "<ol>";

echo "<li>";
echo form_label('Providers','provider');
echo form_dropdown('provider',$providers);
echo "</li>";
echo "<li>";
echo form_label('Message','message');
echo form_textarea('message');
echo "</li>";
echo "</ol>";
echo form_fieldset_close();
?>
<div class="buttons">
 <button type="submit" name="submit" value="Invitation" class="btn positive">
    <span class="save">Send invitation<span>
 </button>
</div>
<?php
echo form_close();
}
