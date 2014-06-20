<?php
$errors_v = validation_errors('<span>', '</span><br />');
    if (!empty($errors_v)) {
        echo '<div class="error">';
        echo $errors_v;
        echo "</div>";
    }
$attributes = array('id' => 'federeg', 'class'=>'');
echo form_open(base_url() . "federations/federation_registration/submit", $attributes);
echo '<div class="row">';
echo '<div class="small-3 large-3 columns"><label for="fedsysname" class="inline right">'.lang('rr_fed_sysname').'</label></div>';
$fedarray = array('name'=>'fedsysname','id'=>'fedsysname', 'value'=>set_value('fedsysname'),'required'=>'required');
echo '<div class="small-9 large-6 columns">'.form_input($fedarray).'</div>';
echo '<div></div>';
echo '</div>';
echo '<div class="row">';
echo '<div class="small-3 large-3 columns"><label for="fedname" class="inline right">'.lang('rr_fed_name').'</label></div>';
$fedarray = array('name'=>'fedname','id'=>'fedname', 'value'=>set_value('fedname'),'required'=>'required');
echo '<div class="small-9 large-6 columns">'.form_input($fedarray).'</div>';
echo '<div></div>';
echo '</div>';

echo '<div class="row">';
echo '<div class="small-3 large-3 columns"><label for="fedurn" class="inline right">'.lang('fednameinmeta').'</label></div>';
$urnarray = array('name'=>'fedurn','placeholder'=>'urn:mace:heanet.ie:edugate:...','id'=>'fedurn','value'=>set_value('fedurn'),'required'=>'required');
echo '<div class="small-9 large-6 columns">'.form_input($urnarray).'</div>';
echo '<div></div>';
echo '</div>';


echo '<div class="row">';
echo '<div class="small-3 large-3 columns"><label class="inline right">'.lang('rr_access').'</label></div>';
echo '<div class="small-9 large-6 columns inline left">';
echo '<div class="small-6 columns">';
echo form_label(lang('rr_detvisibletoothers'),'ispublic');
$pubarray = array('name'=>'ispublic','id'=>'ispublic','value'=>'public');
echo form_checkbox($pubarray);
echo '</div>';
echo '<div class="small-6 columns">';
echo form_label(lang('rr_fedlocalmanaged'),'islocal').'';
$locarray= array('name'=>'islocal','id'=>'islocal','value'=>'islocal','checked'=>'checked');
echo form_checkbox($locarray);
echo '</div>';
echo '</div>';
echo '<div></div>';
echo '</div>';


echo '<div class="row">';
echo '<div class="small-3 large-3 columns"><label for="description" class="inline right">'.lang('rr_description').'</label></div>';
echo '<div class="small-9 large-6 columns">'.form_textarea(array('name' => 'description', 'id' => 'description', 'value' => set_value('description'), 'rows' => 20, 'cols' => 40));
echo '</div>';
echo '<div></div>';
echo '</div>';


echo '<div class="row">';
echo '<div class="small-3 large-3 columns"><label for="termsofuse" class="inline right">'.lang('rr_fed_tou').'</label></div>';
echo '<div class="small-9 large-6 columns">'.form_textarea(array('name' => 'termsofuse', 'id' => 'termsofuse', 'value' => set_value('termsofuse'), 'rows' => 20, 'cols' => 40));
echo '</div>';
echo '<div></div>';
echo '</div>';


$tf = '';

$tf .='<div class="buttons row">';
$tf .='<div class="button-group small-12 large-9 columns text-right">';
$tf .='<button type="reset" name="reset" value="Reset" class="button resetbutton reseticon alert small">
                  '.lang('rr_reset').'</button> ';
$tf .='<button type="submit" name="submit" value="Register" class="button savebutton saveicon small">
                  '.lang('register').'</button>';
$tf .='</div>';
$tf .= '<div class="large-3"></div>';
$tf .= '</div>';
echo $tf;
echo form_close();
?>



