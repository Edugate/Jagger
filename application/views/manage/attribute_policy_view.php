
<div class="specificpolicy">

<div class="ui-widget">
    <?php
    $action = base_url().'manage/attribute_policy/specific/'.$idpid.'/sp';
    $attributes = array('id'=>'formver3');
    echo form_open($action,$attributes);
    echo form_fieldset();
    echo form_label('Select Service Provider','service');
    //echo "<br />";
    $js = 'id="combobox"';
    
    echo form_dropdown('service',$formdown,'0',$js );
    //echo form_submit('submit','submit');
    echo '<span class="buttons"><button type="submit" name="submit" value="submit" class="btn positive v2"><span class="save">Go</span></button></span>';
    echo form_fieldset_close();
    echo form_close()
    ?>
    
	
</div>
    <!--
<button id="toggle">Show underlying select</button>
-->
</div>
<div id="pagetitle"><?php echo lang('rr_attributereleasepolicy');?></div>
<?php
//$this->load->view('autosuggest_script_view');

$idp_link = anchor(base_url()."providers/provider_detail/idp/".$idpid,'<img src="' . base_url() . 'images/icons/home.png" />');

echo '<div id="subtitle"><h3>'.lang('identityprovider').': <a href="'.base_url().'providers/provider_detail/idp/'.$idpid.'">'.$idp_name.'</a></h3><h4>'.$idp_entityid.'</h4></div>';
echo "<div id=\"subtitle\">";
echo "<dl>";
echo "<dd>Supported Attributes <a href=\"".base_url()."manage/supported_attributes/idp/".$idpid."\"><img src=\"".base_url()."images/icons/arrow.png\" /></a></dd>";

echo "<dt>Edit/Add Policies</dt>";

echo "<dd>";
echo "</dd>";
echo "</dl>";
echo "</div>";
?>
<span class="span-24">
<?php



if (!empty($message))
{
    echo "<div class=\"notice\">" . $message . "</div>";
}
if (!empty($error))
{
    echo "<div class=\"alert\">" . $error . "</div>";
}
echo "</span>";

$attributes = array('class' => 'email', 'id' => 'formver2');
$hidden = array('spid' => $spid, 'idpid' => $idpid);
if(!empty($fedid))
{
	$hidden['fedid'] = $fedid;
}
$target = base_url() . "manage/attribute_policy/submit_global";
if (count($attrs_array_newform) > 0)
{
    echo '<span class="span-22">';
    echo '<div class="accordionButton buttons"><button class="btn btn-positive">'.lang('setdefaultpolicyfornewsupattrs').'</button> </div>';
    echo '<div class="accordionContent">';
    echo form_open($target, $attributes, $hidden);
    echo form_fieldset(); 
    echo '<ol>';
    echo '<li>';
    echo form_label(lang('rr_selectsupportedattr'), 'attribute') . "\n";
    echo form_dropdown('attribute', $attrs_array_newform);
    echo '</li>';

    echo '<li>';
    echo form_label(lang('rr_selectpolicytorelease'), 'policy') . "\n";
    echo form_dropdown('policy', array('0' => 'never', '1' => 'only if required', '2' => 'always when needed'));
    echo '</li></ol>';
    echo '<div class="buttons">';
    echo '<button type="submit" name="submit" value="Cancel" class="btn negative"><span class="cancel">'.lang('rr_cancel').'<span></button>';
    echo '<button type="submit" name="submit" value="Add default policy" class="btn positive"><span class="save">'.lang('adddefaultpolicy').'<span></button>';
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
echo '<div class="buttons clear">';
echo anchor('manage/attribute_policy/show_feds/'.$idpid.'','<span class="buttons"><button class="btn btn-positive"><span>Federation attribute release policy</span></button></span>');
echo '</div>';

if (!empty($federations_policy))
{
    
    echo '<span><div>'.$federations_policy.'</div></span>';
}
?>

<span class="span-24">
<?php
if (!empty($specific_policy))
{
    echo $specific_policy;
}
?>
</span>

