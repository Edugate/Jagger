
<?php
$errors_v = validation_errors('<div class="error">', '</div>');
if (!empty($message))
{
    echo '<span data-alert class="alert-box notice message">' . $message . '</span>';
}
if (!empty($error))
{
    echo '<span data-alert class="alert-box alert error">' . $error . '</span>';
}
?>
<?php
if(!empty($success_message))
{
  echo '<div data-alert class="alert-box success">'.$success_message.'</div>';
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

echo '<div class="small-12 columns">';
echo '<div class="medium-3 medium-text-right columns">';
echo '<label for="elock" class="inline right">'.lang('rr_lock_entity').'</label>';
echo '</div>';
echo '<div class="medium-6 columns end">';
echo form_dropdown('elock', $elock,$current_locked);
echo '</div>';
echo '</div>';
echo '<div class="small-12 columns">';
echo '<div class="medium-3 medium-text-right columns">';
echo '<label for="eactive" class="inline right">'.lang('rr_entityactive').'</label>';
echo '</div>';
echo '<div class="medium-6 columns end">';
echo form_dropdown('eactive', $eactive,$current_active);
echo '</div>';
echo '</div>';
echo '<div class="small-12 columns">';
echo '<div class="medium-3 medium-text-right columns">';
echo '<label for="publicvisible" class="inline right">'.lang('rr_visibilityonpublists').'</label>';
echo '</div>';
echo '<div class="medium-6 columns end">';
echo form_dropdown('publicvisible', $publicvisible,$current_publicvisible);
echo '</div>';
echo '</div>';
echo '<div class="small-12 columns">';
echo '<div class="medium-3 medium-text-right columns">';
echo '<label for="extint" class="inline right">'.lang('rr_entitylocalext').'</label>';
echo '</div>';
echo '<div class="medium-6 columns end">';

echo form_dropdown('extint', $extint,$current_extint);
echo '</div>';
echo '</div>';
echo '<div class="small-12 columns">';
echo '<div class="medium-3 medium-text-right columns">';

echo '<label for="validfromdate" class="inline right">'.lang('rr_validfrom').'</label>';
echo '</div>';
echo '<div class="medium-3 large-2 columns">';
echo form_input(array(
            'id'=>'validfromdate',
            'name'=>'validfromdate',
            'value'=>$current_validfromdate,
            'class'=>'datepicker',
            'placeholder'=>'YY-MM-DD'
         ));
echo '</div>';
echo '<div class="medium-3 large-2 columns end">';
echo form_input(array(
            'id'=>'validfromtime',
            'name'=>'validfromtime',
            'value'=>$current_validfromtime,
            'class'=>'inputtime',
            'placeholder'=>'HH:mm',
      ));
echo '</div>';
echo '</div>';
echo '<div class="small-12 columns">';
echo '<div class="medium-3 medium-text-right columns">';
echo '<label for="validuntildate" class="inline right">'.lang('rr_validto').'</label>';
echo '</div>';
echo '<div class="medium-3 large-2 columns">';
echo form_input(array(
           'id'=>'validuntildate',
           'name'=>'validuntildate',
           'value'=>$current_validuntildate,
           'class'=>'datepicker',
           'placeholder'=>'YY-MM-DD'
           ));
echo '</div>';
echo '<div class="medium-3 large-2 columns end">';
echo form_input(
           array('id'=>'validuntiltime',
                 'name'=>'validuntiltime',
                 'value'=>$current_validuntiltime,
                 'class'=>'inputtime',
                 'placeholder'=>'HH:mm',
              )
           );
echo '</div>';
echo '</div>';
echo '<div class="small-12 column text-right"><div class="medium-9 column end"><button name="submit" type="submit" id="submit" value="Modify" class="button savebutton saveicon">'.lang('rr_modify').'</button></div></div>';
echo form_close();
