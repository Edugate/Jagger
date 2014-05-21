<div id="pagetitle"><?php echo lang('rr_federation_regform_title');?></div>
<?php
$errors_v = validation_errors('<span>', '</span><br />');
    if (!empty($errors_v)) {
        echo '<div class="error">';
        echo $errors_v;
        echo "</div>";
    }
$attributes = array('id' => 'federeg', 'class'=>'');
echo form_open(base_url() . "federations/federation_registration/submit", $attributes);
echo form_fieldset(lang('rr_regform'));
echo '<ol><li>';
echo form_label(lang('rr_fed_name'),'fedname');
$fedarray = array('name'=>'fedname','id'=>'fedname', 'value'=>set_value('fedname'),'required'=>'required');
echo form_input($fedarray);
echo '</li><li>';
echo form_label(lang('fednameinmeta'),'fedurn');
$urnarray = array('name'=>'fedurn','placeholder'=>'urn:mace:heanet.ie:edugate:...','id'=>'fedurn','value'=>set_value('fedurn'),'required'=>'required');
echo form_input($urnarray);
echo '</li><li>';
echo form_label(lang('rr_access'),'ispublic');

echo lang('rr_detvisibletoothers').' ';
$pubarray = array('name'=>'ispublic','id'=>'ispublic','value'=>'public');
echo form_checkbox($pubarray);


echo lang('rr_fedlocalmanaged').' ';

$locarray= array('name'=>'islocal','id'=>'islocal','value'=>'islocal','checked'=>'checked');
echo form_checkbox($locarray);
echo '</li><li>';

echo form_label(lang('rr_description'),'description');

echo form_textarea(array('name' => 'description', 'id' => 'description', 'value' => set_value('description'), 'rows' => 20, 'cols' => 40));
echo '</li><li>';

echo form_label(lang('rr_fed_tou'),'termsofuse');

echo form_textarea(array('name' => 'termsofuse', 'id' => 'termsofuse', 'value' => set_value('termsofuse'), 'rows' => 20, 'cols' => 40));
echo '</li></ol>';


$tf = '';

$tf .='<div class="buttons">';
$tf .='<button type="reset" name="reset" value="Reset" class="resetbutton reseticon">
                  '.lang('rr_reset').'</button> ';
$tf .='<button type="submit" name="submit" value="Register" class="savebutton saveicon">
                  '.lang('register').'</button>';
$tf .= '</div>';
echo $tf;
echo form_close();
?>



