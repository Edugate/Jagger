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
$this->table->set_caption('List available Identity Providers');
$this->table->set_heading('Name','add');
$fed_link = anchor(base_url().'federations/manage/show/'.$fed_encoded,'<img src="'.base_url().'images/icons/arrow-in.png"/>');
$mtype = "";
if($memberstype == 'idp')
{
	$mtype = 'Identity Provider(s)';
}
elseif($memberstype == 'sp')
{
	$mtype = 'Service Provider(s)';
}
echo "<div id=\"subtitle\">";
echo "Add new ".$mtype." to federation: ".$federation_name . $fed_link;
echo "</div>";

if($message)
{
	echo $message;
}
echo $this->table->generate($form_elements);
echo '<div class="buttons">';
echo '<button type="reset" name="reset" value="reset" class="btn negative"><span class="cancel">Reset</span></button>';
echo '<button type="submit" name="ass" value="add selected new members" class="btn positive"><span class="save">Add selected new members</span></button>';
echo "</div>";
echo form_close();
