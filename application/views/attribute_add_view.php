<div id="subtitle"><h3><?php echo lang('rr_newattr_title');?></h3></div>
<?php
$errors_v = validation_errors('<span>', '</span><br />');
if (!empty($errors_v)) {
    echo '<div class="error">' . $errors_v . '</div>';
}

echo form_open();
echo form_fieldset('');
echo '<ol>';
echo '<li>';
//echo '<div class="dhelp" ></div>';
echo form_label(lang('attrname').'','attrname');
echo form_input(array(
     'id'=>'attrname',
     'name'=>'attrname',
     'value'=>set_value('attrname'),
     'max-length'=>'128',
     'class'=>'required' 
    ));
echo '</li>';
echo '<li>';
echo form_label(lang('attrfullname'),'attrfullname');
echo form_input(array(
     'id'=>'attrfullname',
     'name'=>'attrfullname',
     'value'=>set_value('attrfullname'),
     'max-length'=>'128',
     'class'=>'required' 
    ));
echo '</li>';
echo '<li>';
echo form_label(lang('attroid'),'attroidname');
echo form_input(array(
     'id'=>'attroidname',
     'name'=>'attroidname',
     'value'=>set_value('attroidname'),
     'max-length'=>'128',
     'class'=>'required' 
    ));
echo '</li>';
echo '<li>';
echo form_label(lang('attrurn'),'attrurnname');
echo form_input(array(
     'id'=>'attrurnname',
     'name'=>'attrurnname',
     'value'=>set_value('attrurnname'),
     'max-length'=>'128',
     'class'=>'required' 
    ));
echo '</li>';
echo '<li>';
echo form_label('' . lang('rr_description') . '','description');
echo form_textarea(array(
    'id' => 'description',
    'name' => 'description',
    'value' => set_value('description'),
    'cols' => 30,
    'rows' => 10,
    'style' => 'font-family: monospace; font-size: smaller'
));
echo '</li>';
echo '</ol>';
echo '<div class="buttons"><button type="submit" name="submit" class="savebutton">'. lang('rr_submit').'</button></div>';
echo form_fieldset_close();

echo form_close();
