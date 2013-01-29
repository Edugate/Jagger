<div id="pagetitle"><?php echo lang('rr_status_mngmt');?></div>
<?php
if (!empty($message))
{
    echo '<span class="message">' . $message . '</span>';
}
if (!empty($error))
{
    echo '<span class="error">' . $error . '</span>';
}
?>
<div id="subtitle"><h3>
    <?php echo lang('serviceprovider').': <a href="'.base_url().'providers/provider_detail/'.$type.'/'.$id.'">'.$name.'</a>'; ?>
</h3><h4><?php echo $entityid;?></h4></div>
<?php
if(!empty($success_message))
{
  echo '<div class="success">'.$success_message.'</div>';
}

$attributes = array('class' => 'span-16', 'id' => 'formver1');
$hidden = array('entid'=>$entid); 
$target = current_url();
$elock = array('1'=>lang('rr_locked'),'0'=>lang('rr_unlocked'));
$eactive = array('0'=>lang('rr_disabled'),'1'=>lang('rr_enabled'));
$extint = array('0'=>lang('rr_external'),'1'=>lang('rr_managedlocally'));
echo form_open($target,$attributes,$hidden);
echo form_fieldset(lang('rr_chngentsettings'));
echo '<ol><li>';
echo form_label(lang('rr_lock_entity'),'elock');
echo form_dropdown('elock', $elock,$current_locked);
echo '</li>';
echo '<li>';
echo form_label(lang('rr_entityactive'),'eactive');
echo form_dropdown('eactive', $eactive,$current_active);
echo '</li>';
echo '<li>';
echo form_label(lang('rr_entitylocalext'),'extint');
echo form_dropdown('extint', $extint,$current_extint);
echo '</li></ol>';
echo form_fieldset_close();
echo '<div class="buttons"><button name="submit" type="submit" id="submit" value="Modify" class="btn positive"><span class="save">'.lang('rr_modify').'</span></button></div>';
echo form_close();
