<?php
if (!empty($message))
{
    echo "<span class=\"message\">" . $message . "</span>";
}
if (!empty($error))
{
    echo "<span class=\"error\">" . $error . "</span>";
}
?>
<div id="subtitle">
    State management for entity: <?php echo $name . ' ('.$entityid.')<a href="'.base_url().'providers/provider_detail/'.$type.'/'.$id.'"><img src="'.base_url().'images/icons/arrow.png"/></a>'; ?>
</div>
<?php
$attributes = array('class' => 'span-16', 'id' => 'formver1');
$hidden = array('entid'=>$entid); 
$target = current_url();
$elock = array('1'=>'locked','0'=>'unlocked');
$eactive = array('0'=>'disabled','1'=>'enabled');
echo form_open($target,$attributes,$hidden);
echo form_fieldset('Change entity settings');
echo '<ol><li>';
echo form_label('Lock entity','elock');
echo form_dropdown('elock', $elock,$current_locked);
echo '</li>';
echo '<li>';
echo form_label('Entity active','eactive');
echo form_dropdown('eactive', $eactive,$current_active);
echo '</li></ol>';
echo form_fieldset_close();
echo '<div class="buttons"><button name="submit" type="submit" id="submit" value="Modify" class="btn positive"><span class="save">Modify</span></button></div>';
echo form_close();
