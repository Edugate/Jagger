<?php

echo form_open();
echo '<fieldset><legend>General</legend><ol>';
echo '<li>';
echo '<label for="buttonname">buttonname</label>';
echo '<input type="text" id="buttonname" name="buttonname" required="required"
                           value="'.$buttonname.'" />';
echo '<li>';
echo '<li>';
echo '<label for="fullname">fullename</label>';
echo '<input type="text" id="fullname" name="fullname" required="required"
                           value="'.$fullname.'" />';
echo '<li>';
echo '</ol></fieldset>';
echo '<fieldset><legend>Memebers</legend>';
echo '<span style="display: none"><input type="hidden" name="fed[0]" id="fed[0]" value="0"/></span>';
echo '<ol>';
echo '<table>';
foreach($multi as $m)
{
   if($m['member'])
   {
      $c = TRUE;
   }
   else
   {
      $c = FALSE;
   }
   $fedid = $m['fedid'];
   $data = array(
    'name'        => 'fed['.$fedid.']',
    'id'          => 'fed['.$fedid.']',
    'value'       => '1',
    'checked'     => $c,
    'style'       => 'margin-right:10px; margin-left: 50px;',
    );
   echo '<li><label for="fed['.$fedid.']">'.$m['fedname'].'</label>'.form_checkbox($data).'</li>';

}
echo '</table>';
echo '</ol>';
echo '</fieldset>';
echo '<div class="buttons"><button type="submit" class="btn btn-positive">Save</button></div>';
echo form_close();
