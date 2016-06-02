


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
echo '<div class="row">';
echo form_open();
echo '<div class="row">';
echo '<div class="small-3 large-2 columns"><label for="buttonname" class="text-right middle">'.lang('tbl_catbtnname').'</label></div>';
echo '<div class="small-9 large-7 columns end"><input type="text" id="buttonname" name="buttonname" 
                           value="'.set_value('buttonname').'" /></div>';
echo '</div>';
echo '<div class="row">';
echo '<div class="small-3 large-2 columns"><label for="fullname" class="text-right middle">'.lang('tbl_catbtnititlename').'</label></div>';
echo '<div class="small-9 large-7 columns end"><input type="text" id="fullname" name="fullname" 
                           value="'.set_value('fullname').'" /></div>';
echo '</div>';
echo '<div class="row">';
echo '<div class="small-3 large-2 columns"><label for="description" class="text-right">'.lang('rr_description').'</label></div>';
echo '<div class="small-9 large-7 columns end"><textarea id="description" name="description" rows="5">';
echo set_value('description');
echo '</textarea></div>';
                        
echo '</div>';
echo '<div class="row"><div class="small-12 columns text-right"><button type="submit" name="formsubmit" value="add" class="button">'.lang('rr_save').'</button></div></div>';
echo form_close();

echo '</div>';