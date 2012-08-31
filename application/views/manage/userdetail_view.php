<?php
if($det)
{
  $tmpl = array ( 'table_open'  => '<table  id="details" class="zebra">' );
  $this->table->set_template($tmpl);
  $this->table->set_caption('Details for user: '.$caption);
  $this->table->set_heading('','');
  echo $this->table->generate($det);


}
