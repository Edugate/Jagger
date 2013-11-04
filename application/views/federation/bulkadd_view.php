<?php
$action = base_url().'federations/manage/bulkaddsubmit';
$hidden = array(
	'fed' => $fed_encoded,
	'memberstype'=>$memberstype,
);
$form_attrs = array('id'=>'dd');
echo form_open($action,$form_attrs,$hidden);
$tmpl = array('table_open' => '<table  id="details" class="tablesorter">');
$this->table->set_template($tmpl);
$this->table->set_caption(lang('listavailableprov'));
$this->table->set_heading(lang('rr_tbltitle_name'),'#');
$fed_link = anchor(base_url().'federations/manage/show/'.$fed_encoded,$federation_name);
$mtype = "";
if($memberstype == 'idp')
{
	$ptitle = lang('rr_addnewidpsnoinv');
}
elseif($memberstype == 'sp')
{
	$ptitle = lang('rr_addnewspsnoinv');
}
echo '<div id="pagetitle">'. $ptitle .' </div>';
echo '<div id="subtitle"><h3>'.lang('rr_federation').': '. $fed_link .'<h3></div>';
if($message)
{
	echo $message;
}
echo $this->table->generate($form_elements);
echo '<div class="buttons">';
echo '<button type="reset" name="reset" value="reset" class="resetbutton reseticon">'.lang('rr_reset').'</button>';
echo '<button type="submit" name="ass" value="add selected new members" class="savebutton saveicon"><span class="save">'.lang('rr_add').'</button>';
echo "</div>";
echo form_close();
