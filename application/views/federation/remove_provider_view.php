<?php
if(!empty($subtitle))
{
   echo '<div id="subtitle" class="span-24">'.$subtitle.'</div>';
}

if(!empty($success_message))
{
    echo '<div class="success">'.$success_message.'</div>';
}

if(!empty($error_message))
{
    echo '<div class="alert">'.$error_message.'</div>';
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
    echo form_fieldset(lang('rmprovfromfed').': '.$fedname);
    echo '<ol>';

    echo '<li>';
    echo form_label(lang('rrprovs'),'provider');
    echo form_dropdown('provider',$providers);
    echo '</li>';
    echo '<li>';
    echo form_label(lang('rr_message'),'message');
    echo form_textarea('message');
    echo '</li>';
    echo '</ol>';
    echo form_fieldset_close();
?>
<div class="buttons">
 <button type="submit" name="submit" value="Remove" class="btn positive">
    <span class="save"><?php echo lang('rr_fedrmmember');?><span>
 </button>
</div>
<?php
    echo form_close();
}
