
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

if(!empty($excluded))
{
      echo ' <div alert-data class="alert-box warning">'.lang('rr_arpexcludedpersp').'</div> ';
}
if(count($tbl_row)>0)
{
	$form_attributes = array('id'=>'formver2');
	$form_hidden = array(
		'idpid'=>$provider_id,
		'spid'=>$requester_id,
		'type'=>'sp',
		);
	echo form_open(base_url().'manage/attributepolicy/submit_multi/'.$provider_id,$form_attributes,$form_hidden);
	echo $this->table->generate($tbl_row);
	echo '<div class="buttons">'.$reset_button . $modify_button.'</div>';
	echo form_close();
}
else
{
	echo '<div alert-data class="alert-box warning">not policy, no supported attributes, no requirememnts found</div>';
}
