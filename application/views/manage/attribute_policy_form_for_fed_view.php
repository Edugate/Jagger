
<?php
$attributes = array('class' => 'email', 'id' => 'formver2');
$hidden = array('fedid' => $fedid, 'idpid' => $idpid);
$target = base_url() . "manage/attributepolicy/submit_fed/".$idpid;
echo form_open($target,$attributes,$hidden);
$tmpl = array ( 'table_open'  => '<table id="detailsnosort">' );
$this->table->set_template($tmpl); 
$this->table->set_heading(lang('attrname'),  lang('policy'));
echo $this->table->generate($tbl_array); 
echo form_close();
