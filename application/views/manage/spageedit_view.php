<?php
$errors_v = validation_errors('<span>', '</span><br />');
if (!empty($errors_v)) {
    echo '<div class="error">' . $errors_v . '</div>';
}
if(!empty($additional_error))
{
    echo '<div class="error">'. $additional_error .'</div>';
}



echo form_open();

if(!empty($newarticle))
{
     echo '<div class="small-12 columns">';
     echo '<div class="medium-text-right medium-3 columns"><label class="inline" for="acode">'.lang('rr_pagecode').'</label></div>';
     echo '<div class="medium-9 columns">'.form_input(array('name'=>'acode','value'=>set_value('acode'))).'</div>';
     echo '</div>';
    

}

echo '<div class="small-12 columns">';
echo '<div class="medium-text-right medium-3 columns"><label class="inline" for="atitle">'.lang('rr_title').'</label></div>';
echo '<div class="medium-9 columns">'.form_input(array('name'=>'atitle','value'=>set_value('atitle',$titlecontent))).'</div>';
echo '</div>';

echo '<div class="small-12 columns">';
echo '<div class="medium-text-right medium-3 columns"><label class="inline" for="atitle">'.lang('rr_category').'</label></div>';
echo '<div class="medium-9 columns">'.form_input(array('name'=>'acategory','value'=>set_value('acategory',$category))).'</div>';
echo '</div>';

echo '<div class="small-12 columns">';
echo '<div class="medium-text-right medium-3 columns"><label class="" for="apublic">'.lang('lbl_spageanonaccess').'</label></div>';
echo '<div class="medium-9 columns">'.form_checkbox(array('name'=>'apublic','value'=>'1','checked'=>$public)).'</div>';
echo '</div>';

echo '<div class="small-12 columns">';
echo '<div class="medium-text-right medium-3 columns"><label for="aenabled">'.lang('rr_enabled').'</label></div>';

echo '<div class="medium-9 columns">'.form_checkbox(array('name'=>'aenabled','value'=>'1','checked'=>$enabled)).'</div>';
echo '</div>';

echo '<div class="small-12 columns">';
echo '<div class="small-12 columns">';
echo '<textarea name="'.$attrname.'" class="advancededitor">'.set_value($attrname, $textcontent).'</textarea>';
echo '</div>';
echo '</div>';
echo '<div class="small-12 columns">';
echo '<div class="small-12 columns">';
echo '<input type="submit" class="button small right" name="update" value="update">';
echo '</div>';
echo '</div>';

echo form_close();
