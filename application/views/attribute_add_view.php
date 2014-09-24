<?php
$errors_v = validation_errors('<span>', '</span><br />');
if (!empty($errors_v)) {
    echo '<div data-alert class="alert-box alert">' . $errors_v . '</div>';
}

echo form_open();
echo form_fieldset('');
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns"><label for="attrname" class="right inline">'.lang('attrname').'</label></div>';
echo '<div class="small-6 large-7 columns end">';
echo form_input(array(
     'id'=>'attrname',
     'name'=>'attrname',
     'value'=>set_value('attrname'),
     'max-length'=>'128',
     'class'=>'required' 
    ));
echo '</div>';
echo '</div>';

echo '<div class="small-12 columns">';
echo '<div class="small-3 columns"><label for="attrfullname" class="right inline">'.lang('attrfullname').'</label></div>';
echo '<div class="small-6 large-7 columns end">';
echo form_input(array(
     'id'=>'attrfullname',
     'name'=>'attrfullname',
     'value'=>set_value('attrfullname'),
     'max-length'=>'128',
     'class'=>'required' 
    ));
echo '</div>';
echo '</div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns"><label for="attroidname" class="right inline">'.lang('attrsaml2').'</label></div>';
echo '<div class="small-6 large-7 columns end">';
echo form_input(array(
     'id'=>'attroidname',
     'name'=>'attroidname',
     'value'=>set_value('attroidname'),
     'max-length'=>'128',
     'class'=>'required' 
    ));
echo '</div>';
echo '</div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns"><label for="attrurnname" class="right inline">'.lang('attrsaml1').'</label></div>';
echo '<div class="small-6 large-7 columns end">';
echo form_input(array(
     'id'=>'attrurnname',
     'name'=>'attrurnname',
     'value'=>set_value('attrurnname'),
     'max-length'=>'128',
     'class'=>'required' 
    ));
echo '</div>';
echo '</div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns"><label for="description" class="right inline">'.lang('rr_description').'</label></div>';
echo '<div class="small-6 large-7 columns end">';
echo form_textarea(array(
    'id' => 'description',
    'name' => 'description',
    'value' => set_value('description'),
    'cols' => 30,
    'rows' => 10,
    'style' => 'font-family: monospace; font-size: smaller'
));
echo '</div>';
echo '</div>';
echo '<div class="buttons small-12"><div class="small-9 large-10 columns end text-right"><button type="submit" name="submit" class="savebutton">'. lang('rr_submit').'</button></div></div>';
echo form_fieldset_close();

echo form_close();
