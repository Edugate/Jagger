<?php
$data['spid'] = $spid;
$this->load->view('/navigations/floatnav_sp_details_view',$data);
if(!empty($error_message))
{
    echo "<span class=\"alert\">".$error_message."</span>";
}
if(!empty($spname))
{
$tmpl = array ( 'table_open'  => '<table id="details" class="zebra">' );
$this->table->set_template($tmpl);
$this->table->set_caption('Service Provider information for: <b>'.$spname."</b> ".$edit_link."");
foreach($sp_details as $row)
{
    if(array_key_exists('header', $row))
    {
        $cell = array('data' => $row['header'], 'class' => 'highlight', 'colspan' => 2);
        $this->table->add_row($cell);
        
    }
	elseif(array_key_exists('2cols',$row))
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
}
