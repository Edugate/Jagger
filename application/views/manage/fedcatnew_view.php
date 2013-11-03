
<div id="pagetitle"><?php echo lang('newfedcategory'); ?></div>


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
                           value="'.set_value('buttonname').'" />';
echo '</li>';
echo '<li>';
echo '<label for="fullname">'.lang('tbl_catbtnititlename').'</label>';
echo '<input type="text" id="fullname" name="fullname" 
                           value="'.set_value('fullname').'" />';
echo '</li>';
echo '<li>';
echo '<label for="description">'.lang('rr_description').'</label>';
echo '<textarea id="description" name="description" rows="5">';
echo set_value('description');
echo '</textarea>';
                        
echo '</li>';
echo '</ol></fieldset>';
echo '<div class="buttons"> <button type="submit" name="formsubmit" value="add" class="btn btn-positive">'.lang('rr_save').'</button></div>';
echo form_close();

