<?php

echo form_open();
echo form_fieldset();
echo '<ul style="list-style-type: none;">';
foreach($merger as $k=>$v)
{
   echo '<li><label for="lang['.$k.']" style="word-break: break-word">'.$v['english'].'</label>';
   
   $input = array('name'=>'lang['.$k.']','id'=>'lang['.$k.']','value'=>$v['to']);
   echo form_input($input);
   echo '</li>';
}

echo '</ul>';
echo '<input type="submit" value="submit" name="submit">';
echo form_fieldset_close();
echo form_close();
