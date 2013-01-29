<?php
$data['spid'] = $spid;

if(!empty($error_message))
{
    echo "<span class=\"alert\">".$error_message."</span>";
}
if(empty($bookmarked))
{
   $bookmark = '<a href="'.base_url().'ajax/bookentity/'.$spid.'" class="bookentity"><img src="'.base_url().'images/icons/star--plus.png" style="float:right"/></a>';
}
else
{
   $bookmark = '<a href="'.base_url().'ajax/delbookentity/'.$spid.'" class="bookentity"><img src="'.base_url().'images/icons/star--minus.png" style="float:right"/></a>';
}
if(!empty($spname))
{
?>
<div id="subtitle"><div style="float: right; display: block"><?php echo $edit_link.'&nbsp;'.$bookmark.'</div><h3>'. lang('serviceprovider').': '.$spname .'</h3></div>';
$this->load->view('/navigations/floatnav_sp_details_view',$data);

$tmpl = array ( 'table_open'  => '<table id="details" class="zebra">' );
$this->table->set_template($tmpl);
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
