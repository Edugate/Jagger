
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

$attributes = array('id' => 'adddefaultpolicy');
$hidden = array('spid' => $spid, 'idpid' => $idpid);
if(!empty($fedid))
{
	$hidden['fedid'] = $fedid;
}
$target = base_url() . 'manage/attribute_policyajax/updatedefault/'.$idpid.'';
if (count($attrs_array_newform) > 0)
{
    echo '<div class="row"><div class="small-12 medium-6 medium-offset-6 column text-right">';
    echo '<div class="accordionButton buttons"><button class="savebutton saveicon small">'.lang('setdefaultpolicyfornewsupattrs').'</button> </div>';
    echo '<div class="accordionContent text-left">';
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
    echo '<button class="small button">'.lang('adddefaultpolicy').'</button>';
    echo '</span>';
    echo form_fieldset_close();

    echo form_close();
    echo '</div></div></div>';
}

if (!empty($default_policy))
{

    echo $default_policy;


}
echo '</div>';
echo '<div id="fedarptab" class="content" >';


if (!empty($federations_policy))
{
    
    echo '<div>'.$federations_policy.'</div>';
}
?>
</div>
<div id="specarptab" class="content" >

<?php
if (!empty($specific_policy))
{
    echo $specific_policy;
}
?>

</div>
<div id="supportedattrtab" class="content" >
<?php
echo lang('rr_supportedattributes').' <a href="'.base_url().'manage/supported_attributes/idp/'.$idpid.'"><img src="'.base_url().'images/icons/arrow.png" /></a>';
?>
</div>
</div>
</div>
<?php

/**
 * globalpolicyupdater modal
 */
echo '<div id="globalpolicyupdater" class="reveal-modal small" data-reveal data-jagger-link="' . base_url('manage/attribute_policyajax/getglobalattrpolicy/' . $idpid . '') . '">
  <h4>' . lang('confirmupdpolicy') . '</h4>
  <h5>'.lang('rr_defaultarp').': <span class="dynamicval"></span></h5>
  <div>
 ';
echo '<div class="attrflow row"></div>';
echo form_open(base_url('manage/attribute_policyajax/updatedefault/'. $idpid.''));
echo form_input(array('name' => 'attribute', 'type' => 'hidden', 'value' => ''));
echo form_input(array('name' => 'idpid', 'type' => 'hidden', 'value' => '' . $idpid . ''));
$dropdown = $this->config->item('policy_dropdown');
echo '<div class="row">';
$dropdown['100'] = lang('dropnotset');
echo '<div class="medium-3 columns medium-text-right"><label for="policy" class="inline" >' . lang('policy') . '</label></div>';
echo '<div class="medium-9 columns">' . form_dropdown('policy', $dropdown, '') . '</div>';
echo '</div>';
echo '<div class="row">';
$buttons = array(
    '<button type="reset" name="cancel" value="cancel" class="button alert modal-close">' . lang('rr_cancel') . '</button>',
    '<div class="yes button">' . lang('btnupdate') . '</div>'
);
echo revealBtnsRow($buttons);
echo '</div></form><a class="close-reveal-modal">&#215;</a></div></div>';

/**
 * fedpolicyupdater modal
 */

echo '<div id="fedpolicyupdater" class="reveal-modal small" data-reveal data-jagger-link="' . base_url('manage/attribute_policyajax/getfedattrpolicy/' . $idpid . '') . '">
  <h4>' . lang('confirmupdpolicy') . '</h4>
  <h5>Fed Attr: <span class="dynamicval"></span></h5>
  <div>
 ';
echo '<div class="attrflow row"></div>';
echo form_open(base_url('manage/attribute_policyajax/updatefed/'. $idpid.''));
echo form_input(array('name' => 'attribute', 'type' => 'hidden', 'value' => ''));
echo form_input(array('name' => 'idpid', 'type' => 'hidden', 'value' => '' . $idpid . ''));
echo form_input(array('name' => 'fedid', 'type' => 'hidden', 'value' => ''));
$dropdown = $this->config->item('policy_dropdown');
echo '<div class="row">';
$dropdown['100'] = lang('dropnotset');
echo '<div class="medium-3 columns medium-text-right"><label for="policy" class="inline" >' . lang('policy') . '</label></div>';
echo '<div class="medium-9 columns">' . form_dropdown('policy', $dropdown, '') . '</div>';
echo '</div>';
echo '<div class="row">';
$buttons = array(
    '<button type="reset" name="cancel" value="cancel" class="button alert modal-close">' . lang('rr_cancel') . '</button>',
    '<div class="yes button">' . lang('btnupdate') . '</div>'
);
echo revealBtnsRow($buttons);
echo '</div></form><a class="close-reveal-modal">&#215;</a></div>';

