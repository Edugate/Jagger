<?php
if (!empty($syswarning))
{
    echo '<div alertdata-alert class="alert-box alert">'.$syswarning.'</div>';
}

if (!empty($error_message))
{
    echo '<div alertdata-alert class="alert-box alert">'.$error_message.'</div>';
}

echo '<div style="text-align: right;">';
echo form_open();
echo form_fieldset();
echo '<div class="small-12 columns">';
foreach($merger as $k=>$v)
{
   if(!isset($v['english']))
   {
       unset($merger[$k]);
       continue;
   }   
   if(strcasecmp($v['english'], $v['to']) == 0)
   {
      $bgcolor = 'yellow';
   }
   else
   {
      $bgcolor = 'white';
   }
   echo '<div class="small-12 columns">';
   echo '<label style="text-align: left; font-weight: bolder" for="lang['.$k.']" style="word-break: break-word">'.$v['english'].' <span style="font-weight: normal;"><small>['.$k.']</small><span></label>';
   
   $input = array('name'=>'lang['.$k.']','id'=>'lang['.$k.']','value'=>$v['to'],'style'       => 'width:90%; background-color: '.$bgcolor.'');
   echo form_input($input);
   echo '</div>';
}

echo '<div class="small-12 columns">';
echo '<input class="savebutton saveicon button" type="submit" value="submit" name="submit">';
echo form_fieldset_close();
echo form_close();
echo '</div>';
