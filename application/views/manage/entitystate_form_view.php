<div id="pagetitle"><?php echo lang('rr_status_mngmt');?></div>

<?php
$errors_v = validation_errors('<div class="error">', '</div>');
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
    <?php echo lang('serviceprovider').': <a href="'.base_url().'providers/detail/show/'.$id.'">'.$name.'</a>'; ?>
</h3><h4><?php echo $entityid;?></h4></div>
<?php
if(!empty($success_message))
{
  echo '<div class="success">'.$success_message.'</div>';
}
if (!empty($errors_v))
{
    echo '<span class="error">' . $errors_v . '</span>';
}

$attributes = array('class' => 'span-16', 'id' => 'formver1');
$hidden = array('entid'=>$entid); 
$target = current_url();
$elock = array('1'=>lang('rr_locked'),'0'=>lang('rr_unlocked'));
$eactive = array('0'=>lang('rr_disabled'),'1'=>lang('rr_enabled'));
$extint = array('0'=>lang('rr_external'),'1'=>lang('rr_managedlocally'));
$publicvisible = array('0'=>lang('rr_hiddeninpubliclist'),'1'=>lang('rr_visibleinpubliclist'));
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
echo form_label(lang('rr_visibilityonpublists'),'publicvisible');
echo form_dropdown('publicvisible', $publicvisible,$current_publicvisible);
echo '</li>';
echo '<li>';
echo form_label(lang('rr_entitylocalext'),'extint');
echo form_dropdown('extint', $extint,$current_extint);
echo '</li>';
echo '<li>';
echo form_label(lang('rr_validfrom'),'validfromdate');
echo form_input(array(
            'id'=>'validfromdate',
            'name'=>'validfromdate',
            'value'=>$current_validfromdate,
            'class'=>'inputdate validfrom',
            'placeholder'=>'YY-MM-DD'
         ));
echo form_input(array(
            'id'=>'validfromtime',
            'name'=>'validfromtime',
            'value'=>$current_validfromtime,
            'class'=>'inputtime',
            'placeholder'=>'HH:mm',
      ));
echo '</li>';
echo '<li>';
echo form_label(lang('rr_validto'),'validuntildate');
echo form_input(array(
           'id'=>'validuntildate',
           'name'=>'validuntildate',
           'value'=>$current_validuntildate,
           'class'=>'inputdate validto',
           'placeholder'=>'YY-MM-DD'
           ));
echo form_input(
           array('id'=>'validuntiltime',
                 'name'=>'validuntiltime',
                 'value'=>$current_validuntiltime,
                 'class'=>'inputtime',
                 'placeholder'=>'HH:mm',
              )
           );
echo '</li></ol>';
echo form_fieldset_close();
echo '<div class="buttons"><button name="submit" type="submit" id="submit" value="Modify" class="savebutton saveicon">'.lang('rr_modify').'</button></div>';
echo form_close();
