<?php
echo "<h3>".$caption."</h3>";
$attributes = array('class' => 'email', 'id' => 'formver2');
$hidden = array('fedid' => $fedid, 'idpid' => $idpid);
$target = base_url() . "manage/attribute_policy/submit_fed/".$idpid;
echo form_open($target,$attributes,$hidden);
$tmpl = array ( 'table_open'  => '<table border=\"0\" id="details" class="tablesorter">' );
$this->table->set_template($tmpl); 
$this->table->set_caption('Attribute Release Policy for federation');
$this->table->set_heading('Attribute name',  'Policy');
echo $this->table->generate($tbl_array); 
echo form_close();
