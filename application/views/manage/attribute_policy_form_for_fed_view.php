
<div id="pagetitle"><?php echo lang('rr_arpforfed') . ': <a href="'.base_url().'federations/manage/show/'.$fedencoded.'">'.$fedname.'</a>';?></div>
<div id="subtitle"><h3><?php echo '<a href="'.base_url().'providers/detail/show/'.$idpid.'">'.$idpname.'</a>'; ?></h3><h4><?php echo $entityid; ?></h4></div>
<?php

$attributes = array('class' => 'email', 'id' => 'formver2');
$hidden = array('fedid' => $fedid, 'idpid' => $idpid);
$target = base_url() . "manage/attribute_policy/submit_fed/".$idpid;
echo form_open($target,$attributes,$hidden);
$tmpl = array ( 'table_open'  => '<table border=\"0\" id="details">' );
$this->table->set_template($tmpl); 
$this->table->set_caption(lang('rr_arpforfed'));
$this->table->set_heading(lang('attrname'),  lang('policy'));
echo $this->table->generate($tbl_array); 
echo form_close();
