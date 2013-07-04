<?php
echo '<div style="text-align: right;">';
echo form_open();
echo form_fieldset();
echo '<ul style="list-style-type: none;">';
foreach($merger as $k=>$v)
{
   echo '<li style="margin: 5px;background-color: #cfcfcf;"><label style="text-align: left; font-weight: bolder" for="lang['.$k.']" style="word-break: break-word">'.$v['english'].' <span style="font-weight: normal;"><small>['.$k.']</small><span></label>';
   
   $input = array('name'=>'lang['.$k.']','id'=>'lang['.$k.']','value'=>$v['to'],'style'       => 'width:90%');
   echo form_input($input);
   echo '</li>';
}

echo '</ul>';
echo '<input class="btn positive" type="submit" value="submit" name="submit">';
echo form_fieldset_close();
echo form_close();
echo '</div>';
