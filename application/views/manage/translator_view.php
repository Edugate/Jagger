<?php
echo '<div style="text-align: right;">';
echo form_open();
echo form_fieldset();
echo '<ul style="list-style-type: none;">';
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
   echo '<li style="margin: 5px;background-color: #cfcfcf;"><label style="text-align: left; font-weight: bolder" for="lang['.$k.']" style="word-break: break-word">'.$v['english'].' <span style="font-weight: normal;"><small>['.$k.']</small><span></label>';
   
   $input = array('name'=>'lang['.$k.']','id'=>'lang['.$k.']','value'=>$v['to'],'style'       => 'width:90%; background-color: '.$bgcolor.'');
   echo form_input($input);
   echo '</li>';
}

echo '</ul>';
echo '<input class="savebutton saveicon button" type="submit" value="submit" name="submit">';
echo form_fieldset_close();
echo form_close();
echo '</div>';
