
<div class="iui-widget small-12 columns">
    <?php
    $action = base_url().'manage/attributepolicy/specific/'.$idpid.'/sp';
    $attributes = array('id'=>'formver3');
    echo '<div class="small-12 columns">';
       echo form_open($action,$attributes);
       echo '<div class="small-3 columns">'.jform_label(''.lang('rr_selectsp').'','service').'</div>';
       $js = 'id="itestcombo"';
    
       echo '<div class="small-3 columns">'.form_dropdown('service',$formdown,'0',$js ).'</div>';
       echo '<div class="small-6 columns"><button type="submit" name="submit" value="submit" class="savebutton nexticon v2 tiny">'.lang('rr_go').'</button></div>';
       echo form_close();
    echo '</div>';
    ?>
</div>
<?php
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
<div id="arptabsi" class="itabs">
<ul  class="tabs" data-tab><li class="tab-title active"><a href="#defaultarptab"><?php echo lang('rr_defaultarp');?></a></li><li class="tab-title"><a href="#fedarptab"><?php echo lang('rr_arpforfeds');?></a></li><li class="tab-title"><a href="#specarptab"><?php echo lang('rr_specpolicies'); ?></a></li><li class="tab-title"><a href="#supportedattrtab"><?php echo lang('rr_supportedattributes'); ?></a></li></ul>
<div class="tabs-content">
<div id="defaultarptab" class="content active">
<?php

$attributes = array('class' => 'email', 'id' => 'formver2');
$hidden = array('spid' => $spid, 'idpid' => $idpid);
if(!empty($fedid))
{
	$hidden['fedid'] = $fedid;
}
$target = base_url() . 'manage/attributepolicy/submit_global';
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
echo '<div id="fedarptab" class="content" >';

echo '<div class="buttons clear">';
echo anchor('manage/attributepolicy/show_feds/'.$idpid.'','<span class="buttons"><button class="savebutton saveicon small">'.lang('rr_arpforfed').'</button></span>');
echo '</div>';

if (!empty($federations_policy))
{
    
    echo '<span><div>'.$federations_policy.'</div></span>';
}
?>
</div>
<div id="specarptab" class="content" >
<span class="span-24">
<?php
if (!empty($specific_policy))
{
    echo $specific_policy;
}
?>
</span>
</div>
<div id="supportedattrtab" class="content" >
<?php
echo lang('rr_supportedattributes').' <a href="'.base_url().'manage/supported_attributes/idp/'.$idpid.'"><img src="'.base_url().'images/icons/arrow.png" /></a>';
?>
</div>
</div>
</div>
