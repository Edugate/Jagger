<?php
$errors_v = validation_errors('<span>', '</span><br />');
if (!empty($errors_v)) {
    echo '<div data-alert class="alert-box alert">' . $errors_v . '</div>';
}

echo form_open();

echo '<div class="small-12 columns">';
echo '<div class="medium-3 columns medium-text-right"><label for="attrname">'.lang('attrname').'</label></div>';
echo '<div class="medium-9 large-7 columns end">';
echo form_input(array(
     'id'=>'attrname',
     'name'=>'attrname',
     'value'=>set_value('attrname','',FALSE),
     'max-length'=>'128',
     'class'=>'required' 
    ));
echo '<div class="dhelp" style="width: auto">'.lang('attrname').': is used as an attribute id in attribute release policy</div>';
echo '</div>';
echo '</div>';

echo '<div class="small-12 columns">';
echo '<div class="medium-3 columns medium-text-right"><label for="attrfullname">'.lang('attrfullname').'</label></div>';
echo '<div class="medium-9 large-7 columns end">';
echo form_input(array(
     'id'=>'attrfullname',
     'name'=>'attrfullname',
     'value'=>set_value('attrfullname','',FALSE),
     'max-length'=>'128',
     'class'=>'required' 
    ));
echo '</div>';
echo '</div>';
echo '<div class="small-12 columns">';
echo '<div class="medium-3 columns medium-text-right"><label for="attroidname">'.lang('attrsaml2').'</label></div>';
echo '<div class="medium-9 large-7 columns end">';
echo form_input(array(
     'id'=>'attroidname',
     'name'=>'attroidname',
     'value'=>set_value('attroidname','',FALSE),
     'max-length'=>'128',
     'class'=>'required' 
    ));
echo '</div>';
echo '</div>';
echo '<div class="small-12 columns">';
echo '<div class="medium-3 columns medium-text-right"><label for="attrurnname">'.lang('attrsaml1').'</label></div>';
echo '<div class="medium-9 large-7 columns end">';
echo form_input(array(
     'id'=>'attrurnname',
     'name'=>'attrurnname',
     'value'=>set_value('attrurnname','',FALSE),
     'max-length'=>'128',
     'class'=>'required' 
    ));
echo '</div>';
echo '</div>';
echo '<div class="small-12 columns">';
echo '<div class="medium-3 columns medium-text-right"><label for="description">'.lang('rr_description').'</label></div>';
echo '<div class="medium-9 large-7 columns end">';
echo form_textarea(array(
    'id' => 'description',
    'name' => 'description',
    'value' => set_value('description','',FALSE),
    'cols' => 30,
    'rows' => 10,
    'style' => 'font-family: monospace; font-size: smaller'
));
echo '</div>';
echo '</div>';
echo '<div class="small-12 column button-group"><div class="large-10 columns end text-right"><a href='.base_url('attributes/attributes/show').' class="button alert">Cancel</a><button type="submit" name="submit" class="button">'. lang('rr_submit').'</button></div></div>';


echo form_close();

