<div id="subtitle"><h3><?php echo lang('rr_userslist');?></h3></div>
<?php
$tmpl = array ( 'table_open'  => '<table  id="details" class="userlist">' );

$this->table->set_template($tmpl);
$this->table->set_empty("&nbsp;"); 
$this->table->set_heading('username','fullname','email','last login','ip');
echo $this->table->generate($userlist);
$this->table->clear();

