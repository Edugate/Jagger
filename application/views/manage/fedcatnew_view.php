


<?php
    $errors_v = validation_errors('<span class="span-12">', '</span><br />');
    if (!empty($errors_v)) {
        echo '<div class="alert-box alert" data-alert>';
        echo $errors_v;
        echo "</div>";
    }
    if(!empty($success_message))
    {
        echo '<div class="alert-box success" data-alert>'.$success_message.'</div>';
        
    }

echo form_open();
echo '<div class="small-12 columns">';
echo '<div class="small-3 large-2 columns"><label for="buttonname" class="inline right">'.lang('tbl_catbtnname').'</label></div>';
echo '<div class="small-6 large-7 columns"><input type="text" id="buttonname" name="buttonname" 
                           value="'.set_value('buttonname').'" /></div>';
echo '<div class="small-1 end"></div>';
echo '</div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 large-2 columns"><label for="fullname" class="inline right">'.lang('tbl_catbtnititlename').'</label></div>';
echo '<div class="small-6 large-7 columns"><input type="text" id="fullname" name="fullname" 
                           value="'.set_value('fullname').'" /></div>';
echo '<div class="small-1 end"></div>';
echo '</div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 large-2 columns"><label for="description" class="inline right">'.lang('rr_description').'</label></div>';
echo '<div class="small-6 large-7 columns"><textarea id="description" name="description" rows="5">';
echo set_value('description');
echo '</textarea></div>';
echo '<div class="small-1 end"></div>';
                        
echo '</div';
echo '<div class="small-12 columns buttons"><div class="small-9 large-9 columns"><button type="submit" name="formsubmit" value="add" class="button savebutton saveicon right">'.lang('rr_save').'</button><div></div></div>';
echo form_close();

