<?php
$data['idpid'] = $idpid;
$this->load->view('/navigations/floatnav_idp_details_view',$data);
if(!empty($alert_message))
{
	echo "<div class=\"alert\">";
	foreach($alert_message as $err)
	{
		echo "<span>".$err."</span><br>";
	}
	echo "</div>";
}
$tmpl = array ( 'table_open'  => '<table id="details" class="zebra">' );
$this->table->set_template($tmpl);
if(empty($bookmarked))
{
   $bookmark = '<a href="'.base_url().'ajax/bookentity/'.$idpid.'" class="bookentity"><img src="'.base_url().'images/icons/star--plus.png" style="float:right"/></a>';
}
else
{
   $bookmark = '<a href="'.base_url().'ajax/delbookentity/'.$idpid.'" class="bookentity"><img src="'.base_url().'images/icons/star--minus.png" style="float:right"/></a>';
}
$this->table->set_caption('Identity Provider information for: <b>'.$idpname.'</b> '.$edit_link.$bookmark.'');
//$this->table->set_heading('Name','Detail');
foreach($idp_details as $row)
{
    if(array_key_exists('header', $row))
    {
        $cell = array('data' => $row['header'], 'class' => 'highlight', 'colspan' => 2);
        $this->table->add_row($cell);
        
    }
    elseif (array_key_exists('2cols', $row))
{
        $cell = array('data' => $row['2cols'], 'colspan' => 2);
        $this->table->add_row($cell);
        
}
    else
    {
        $this->table->add_row($row['name'], $row['value']);
    }
    
}
echo $this->table->generate();
$this->table->clear();
