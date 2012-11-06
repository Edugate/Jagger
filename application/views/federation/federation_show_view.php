<?php
$tmpl = array('table_open'=>'<table id="details" class="zebra">');
$this->table->set_template($tmpl);
$this->table->set_heading('','');
if(!$bookmarked)
{
    $blink = '<a href="'.base_url().'ajax/bookfed/'.$federation_id.'" class="bookentity"><img src="'.base_url().'images/icons/star--plus.png" /></a>'; 
}
else
{
    $blink = '<a href="'.base_url().'ajax/delbookfed/'.$federation_id.'" class="bookentity"><img src="'.base_url().'images/icons/star--minus.png" /></a>';
}
$this->table->set_caption('Details for "'.$federation_name.'" Federation '.$blink);
echo $this->table->generate($tbl);
