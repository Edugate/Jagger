<?php
    $errors_v = validation_errors('<span class="span-12">', '</span><br />');
    if (!empty($errors_v)) {
        echo '<div class="error">';
        echo $errors_v;
        echo "</div>";
    }
    if(!empty($success_message))
    {
        echo '<div class="success">'.$success_message.'</div>';
        
    }

echo form_open();
echo '<fieldset><legend>'.lang('general').'</legend><ol>';
echo '<li>';
echo '<label for="buttonname">'.lang('tbl_catbtnname').'</label>';
echo '<input type="text" id="buttonname" name="buttonname" 
                           value="'.$buttonname.'" />';
echo '</li>';
echo '<li>';
echo '<label for="fullname">'.lang('tbl_catbtnititlename').'</label>';
echo '<input type="text" id="fullname" name="fullname" 
                           value="'.$fullname.'" />';
echo '</li>';
echo '<li>';
echo '<label for="description">'.lang('rr_description').'</label>';
echo '<textarea id="description" name="description" rows="5">';
echo $description;
echo '</textarea>';
                        
echo '</li>';
echo '</ol></fieldset>';
echo '<fieldset><legend>'.lang('rrfedcatmembers').'</legend>';
echo '<span style="display: none"><input type="hidden" name="fed[controlkey]" id="fed[controlkey]" value="0"/></span>';
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
    'name'        => 'fed[]',
    'id'          => 'fed[]',
    'value'       => ''.$fedid.'',
    'checked'     => $c,
    'style'       => 'margin-right:10px; margin-left: 70px;',
    );
   echo '<li><label for="fed[]">'.$m['fedname'].'</label>'.form_checkbox($data).'</li>';

}
echo '</table>';
echo '</ol>';
echo '</fieldset>';
echo '<div class="buttons"><button type="submit" id="rmfedcategory" name="formsubmit" value="remove" class="resetbutton deleteicon">'.lang('rr_remove').'</button> <button type="submit" name="formsubmit" value="update" class="savebutton saveicon">'.lang('rr_save').'</button></div>';
echo form_close();

echo confirmDialog(''.lang('title_confirm').'', ''.sprintf(lang('douwanttoremove'),lang('fedcategory')).'', ''.lang('rr_yes').'', ''.lang('rr_no').'');
