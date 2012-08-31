<?php
$tmpl = array('table_open'=>'<table id="details" class="zebra">');
$this->table->set_template($tmpl);
$this->table->set_heading('','');
$this->table->set_caption('Details for "'.$federation_name.'" Federation');
echo $this->table->generate($tbl);
