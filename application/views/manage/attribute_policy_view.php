
<div class="specificpolicy">

<div class="ui-widget">
    <?php
    $action = base_url().'manage/attribute_policy/specific/'.$idpid.'/sp';
    $attributes = array('id'=>'formver3');
    echo '<div class="small-12 columns">';
    echo form_open($action,$attributes);
    echo '<div class="small-3 columns">'.jform_label(''.lang('rr_selectsp').'','service').'</div>';
    //echo "<br />";
    $js = 'id="icombobox"';
    
    echo '<div class="small-3 columns">'.form_dropdown('service',$formdown,'0',$js ).'</div>';
    echo '<div class="small-6 columns"><button type="submit" name="submit" value="submit" class="savebutton nexticon v2 tiny">'.lang('rr_go').'</button></div>';
    echo form_close();
    echo '</div>';
    ?>
    
	
</div>
    <!--
<button id="toggle">Show underlying select</button>
-->
</div>
<?php
//$this->load->view('autosuggest_script_view');

$idp_link = anchor(base_url().'providers/detail/show/'.$idpid,'<img src="' . base_url() . 'images/icons/home.png" />');

?>
<div class="small-12 columns">
<?php



if (!empty($message))
{
    echo '<div  data-alert class="alert-box info">'. $message . '</div>';
}
if (!empty($error))
{
    echo '<div  data-alert class="alert-box alert">'. $error .'</div>';
}
echo '</div>';

?>
<div id="arptabs">
<ul><li><a href="#defaultarptab"><?php echo lang('rr_defaultarp');?></a></li><li><a href="#fedarptab"><?php echo lang('rr_arpforfeds');?></a></li><li><a href="#specarptab"><?php echo lang('rr_specpolicies'); ?></a></li><li><a href="#supportedattrtab"><?php echo lang('rr_supportedattributes'); ?></a></li></ul>

<div id="defaultarptab">
<?php

$attributes = array('class' => 'email', 'id' => 'formver2');
$hidden = array('spid' => $spid, 'idpid' => $idpid);
if(!empty($fedid))
{
	$hidden['fedid'] = $fedid;
}
$target = base_url() . 'manage/attribute_policy/submit_global';
if (count($attrs_array_newform) > 0)
{
    echo '<span class="span-22">';
    echo '<div class="accordionButton buttons"><button class="savebutton saveicon">'.lang('setdefaultpolicyfornewsupattrs').'</button> </div>';
    echo '<div class="accordionContent">';
    echo form_open($target, $attributes, $hidden);
    echo form_fieldset(); 
    echo '<ol>';
    echo '<li>';
    echo form_label(lang('rr_selectsupportedattr'), 'attribute') . "\n";
    echo form_dropdown('attribute', $attrs_array_newform,set_value('attribute'));
    echo '</li>';

    echo '<li>';
    echo form_label(lang('rr_selectpolicytorelease'), 'policy') . "\n";
    echo form_dropdown('policy', array('0' => ''.lang('dropnever').'', '1' => ''.lang('dropokreq').'', '2' => ''.lang('dropokreqdes').''),set_value('policy'));
    echo '</li></ol>';
    echo '<div class="buttons">';
    echo '<button type="submit" name="submit" value="Cancel" class="resetbutton reseticon small alert">'.lang('rr_cancel').'</button>';
    echo '<button type="submit" name="submit" value="Add default policy" class="savebutton saveicon small">'.lang('adddefaultpolicy').'</button>';
    echo '</span>';
    echo form_fieldset_close();

    echo form_close();
    echo '</div></span>';
}

if (!empty($default_policy))
{
    echo '<span class="span-24 clear">';
    echo $default_policy;
    echo '</span>';

}
echo '</div>';
echo '<div id="fedarptab">';

echo '<div class="buttons clear">';
echo anchor('manage/attribute_policy/show_feds/'.$idpid.'','<span class="buttons"><button class="savebutton saveicon small">'.lang('rr_arpforfed').'</button></span>');
echo '</div>';

if (!empty($federations_policy))
{
    
    echo '<span><div>'.$federations_policy.'</div></span>';
}
?>
</div>
<div id="specarptab">
<span class="span-24">
<?php
if (!empty($specific_policy))
{
    echo $specific_policy;
}
?>
</span>
</div>
<div id="supportedattrtab">
<?php
echo lang('rr_supportedattributes').' <a href="'.base_url().'manage/supported_attributes/idp/'.$idpid.'"><img src="'.base_url().'images/icons/arrow.png" /></a>';
?>
</div>
</div>
