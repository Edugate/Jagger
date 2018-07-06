<?php
$subtitle = '<div id="subtitle"><dl><dt>'.lang('rr_provider').'</dt>';
if($isLocked)
{
    $subtitle .='<dd>' . $idp_name . ' (' . $idp_entityid . ') <span class="notice">locked</span></dd>';
}

$subtitle .='<dt>'.lang('serviceprovider').'</dt><dd>' . $sp_name . ' (' . $sp_entityid . ') </dd></dl></div>';
if(empty($values))
{
   $values = '';
}
echo $subtitle;
echo validation_errors('<div class="error">', '</div>');
echo '<div class="small-12 column">'.ucfirst(lang('customarpforattr')).': '.$attribute_name.'</div>';
echo form_open($form_action);

echo '<div class="small-12 column">';

$options=array('permit'=>''.lang('attrpermited').'','deny'=>''.lang('attrdenied').'');
echo '<div class="small-3 column text-right">';
echo form_label(''.lang('policy').'','policy');
echo '</div>';
echo '<div class="small-9 column">';
echo form_dropdown('policy' , $options, $policy_selected);
echo '</div>';
echo '</div>';

echo '<div class="small-12 column">';
echo '<div class="small-3 column text-right">';
echo form_label(''.lang('permdenvalues').'','values');
echo '</div>';
echo '<div class="small-9 column">';
echo '<span class="label secondary">'.lang('rr_usecommaasdelimiter').';<b>note:</b> '.lang('rr_customattrscopednote').'</span>';
echo form_textarea(
      array('id'=>'values',
            'name'=>'values',
            'value'=>set_value('values',$values))
    );
echo '</div>';
echo '</div>';
$btns = array('<button type="submit" name="submit" value="Save" class="savebutton saveicon">'.lang('rr_save').'</button>');

echo '<div class="small-12 column buttons">';
echo revealBtnsRow($btns);
echo '</div>';




echo form_close();




