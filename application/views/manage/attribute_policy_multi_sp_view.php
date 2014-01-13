<div id="pagetitle"><?php echo lang('rr_specarpforsp');?></div>

<?php
$tmpl = array ( 'table_open'  => '<table id="detailsnosort">' );

$this->table->set_template($tmpl);
$this->table->set_heading(''.lang('attrname').'',  ''.lang('rr_status').'', ''.lang('reasonofreq').'',''.lang('policy').'');
$tbl_row = array();
foreach($arps as $arp)
{

	$attrname = $arp['attr_name'];
	if(empty($arp['supported']))
	{
		$attrname = '<span class="alert">'.$attrname.'</span>'.showBubbleHelp(''.lang('attrnotsupported').'');
	}
	if(empty($arp['req_status']))
	{
		$status = lang('notrequired');
		$reason = '';
	}
	else
	{
		$status = $arp['req_status'];
		$reason = $arp['req_reason'];
	}

	if($arp['attr_policy'] === null)
	{
		$arp['attr_policy'] = 100;
	}
	$policy_select = form_dropdown('policy['.$arp['attr_id'].']', $policy_dropdown, $arp['attr_policy']);

	$tbl_row[] = array(
		$attrname,$status,$reason,$policy_select,
	);

}
$reset_button = '';
$modify_button = '<button type="submit" value="modify" class="savebutton saveicon">'.lang('rr_modify').'</button>';
$sp_link = anchor(base_url()."providers/detail/show/".$requester_id,$requester);
$idp_link = anchor(base_url()."providers/detail/show/".$provider_id,$provider);
$attr_req_link = anchor(base_url()."manage/attribute_requirement/sp/".$requester_id,'<img src="' . base_url() . 'images/icons/arrow.png" />');
echo '<div id="subtitle">';
echo '<h3>'.lang('identityprovider').': '.$idp_link.'<br/> <small>'.$provider_entityid.'</small></h3>';
echo '<h4>Requester: '.$sp_link;
if(!empty($excluded))
{
      echo ' <span class="lbl lbl-disabled">'.lang('rr_arpexcludedpersp').'</span> ';
}
echo '<br /><small>'.$requester_entityid.'</small></h4>';
echo '<dl>';
echo '<dd>'.lang('rr_supportedattributes').' <a href="'.base_url().'manage/supported_attributes/idp/'.$provider_id.'"><img src="'.base_url().'images/icons/arrow.png" /></a></dd>';
echo '<dd>'.lang('rr_attributereleasepolicy').'<a href="'.base_url().'manage/attribute_policy/globals/'.$provider_id.'"><img src="'.base_url().'images/icons/arrow.png" /></a></dd>';
echo '<dd>'.lang('rr_attributerequirements') . $attr_req_link.'</dd>';
echo '</dl>';
echo '</div>';
if(count($tbl_row)>0)
{
	$form_attributes = array('id'=>'formver2');
	$form_hidden = array(
		'idpid'=>$provider_id,
		'spid'=>$requester_id,
		'type'=>'sp',
		);
	echo form_open(base_url().'manage/attribute_policy/submit_multi/'.$provider_id,$form_attributes,$form_hidden);
	echo $this->table->generate($tbl_row);
	echo '<div class="buttons">'.$reset_button . $modify_button.'</div>';
	echo form_close();
}
else
{
	echo "<span class=\"alert\">not policy, no supported attributes, no requirememnts found</span>";
}
