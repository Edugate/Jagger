
<?php
if($det)
{
?>
<div id="subtitle"><h3><?php echo lang('rr_detforuser').': '.$caption; ?> </h3></div>
<?php
  $tmpl = array ( 'table_open'  => '<table  id="detailsnosort" class="zebra">' );
  $this->table->set_heading(''.lang('rr_tbltitle_name').'',''.lang('rr_details').'');
  $this->table->set_template($tmpl);
  echo $this->table->generate($det);
  $this->table->clear();

}
