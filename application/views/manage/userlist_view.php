<?php
$tmpl = array ( 'table_open'  => '<table  id="details" class="zebra">' );
$this->table->set_template($tmpl);
$this->table->set_caption('List users in the system');
$this->table->set_heading('username','fullname','email','last login','ip');
echo $this->table->generate($userlist);

