<?php
$idp_link = anchor(base_url() . 'providers/detail/show/' . $idp_id, '<img src="' . base_url() . 'images/icons/home.png"/>');
$sp_link = anchor(base_url() . 'providers/detail/show/' . $sp_id, '<img src="' . base_url() . 'images/icons/block-share.png"/>');
$subtitle = '<div id="subtitle"><dl><dt>'.lang('rr_provider').'</dt>';
if($locked)
{
    $subtitle .='<dd>' . $idp_name . ' (' . $idp_entityid . ') <span class="notice">locked</span>' . $idp_link . '</dd>';
}
else
{
    $subtitle .='<dd>' . $idp_name . ' (' . $idp_entityid . ') ' . $idp_link . '</dd>';
}

$subtitle .='<dt>'.lang('serviceprovider').'</dt><dd>' . $sp_name . ' (' . $sp_entityid . ') ' . $sp_link . '</dd></dl></div>';
if(empty($values))
{
   $values = '';
}
echo $subtitle;
echo validation_errors('<div class="error">', '</div>'); 
echo form_open($form_action);
echo form_fieldset(''.lang('customarpforattr').': '.$attribute_name);

echo '<ol><li>';
$options=array('permit'=>''.lang('attrpermited').'','deny'=>''.lang('attrdenied').'');
echo form_label(''.lang('policy').'','policy');
echo form_dropdown('policy' , $options, $policy_selected);
echo "</li>";
echo "<li>";
echo form_label(''.lang('permdenvalues').' <br /><small>'.lang('rr_usecommaasdelimeter').'<br />note: '.lang('rr_customattrscopednote').'</small>','values');
echo form_textarea(
      array('id'=>'values',
            'name'=>'values',
            'value'=>set_value('values',$values))
    );
echo '</li></ol>';
echo form_fieldset_close();
?>
<div class="buttons">
    <button type="submit" name="submit" value="Save" class="savebutton saveicon">
        <?php echo lang('rr_save'); ?></button>
</div>
<?php



echo form_close();




