<?php

$errors_v = validation_errors('<span class="span-12">', '</span><br />');
    if (!empty($errors_v)) {
        echo '<div class="error">';
        echo $errors_v;
        echo "</div>";
    }


$attributes = array('id' => 'federeg', 'class'=>'span-18');
echo form_open(base_url() . "federations/federation_registration/submit", $attributes);



echo form_fieldset('Register Federation');
echo "<ol>\n";
echo "<li>\n";

echo form_label('Federation Name','fedname');

$fedarray = array('name'=>'fedname','id'=>'fedname', 'value'=>set_value('fedname'),'required'=>'required');
echo form_input($fedarray);
echo "</li>\n";

echo "<li>\n";

echo form_label('Federation URN','fedurn');

$urnarray = array('name'=>'fedurn','placeholder'=>'urn:mace:heanet.ie:edugate:...','id'=>'fedurn','value'=>set_value('fedurn'),'required'=>'required');
echo form_input($urnarray);
echo "</li>\n";
echo "<li>\n";

echo form_label('Access','ispublic');

/**
 * @todo make checked based on input 
 */

echo "Public visible";
$pubarray = array('name'=>'ispublic','id'=>'ispublic','value'=>'public');
echo form_checkbox($pubarray);


echo 'Local federation';

$locarray= array('name'=>'islocal','id'=>'islocal','value'=>'islocal');
echo form_checkbox($locarray);
echo "</li>";
echo "<li>";

echo form_label('Short description','description');

echo form_textarea(array('name' => 'description', 'id' => 'description', 'value' => set_value('description'), 'rows' => 20, 'cols' => 40));
echo "</li>";
echo "<li>";

echo form_label('Terms Of Use','termsofuse');

echo form_textarea(array('name' => 'termsofuse', 'id' => 'termsofuse', 'value' => set_value('termsofuse'), 'rows' => 20, 'cols' => 40));
echo "</li>";
echo "</ol>";


$tf = '';

$tf .='<div class="buttons">';
$tf .='<button type="reset" name="reset" value="Reset" class="button negative">
                  <span class="reset">Reset</span></button>';
$tf .='<button type="submit" name="submit" value="Register" class="button positive">
                  <span class="save">Register</span></button>';
$tf .= '</div>';
echo $tf;
echo form_close();
?>



