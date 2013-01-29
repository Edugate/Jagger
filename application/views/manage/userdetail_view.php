
<?php
if($det)
{
?>
<div id="subtitle"><h3><?php echo lang('rr_detforuser').': '.$caption; ?> </h3></div>
<?php
  $tmpl = array ( 'table_open'  => '<table  id="details" class="zebra">' );
  $this->table->set_heading('Name','Details');
  $this->table->set_template($tmpl);
  echo $this->table->generate($det);
  $this->table->clear();

}
