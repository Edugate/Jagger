
<?php
if($det)
{
?>
<?php
  $tmpl = array ( 'table_open'  => '<table  id="detailsnosort" class="zebra">' );
  $this->table->set_heading(''.lang('rr_tbltitle_name').'',''.lang('rr_details').'');
  $this->table->set_template($tmpl);
  echo $this->table->generate($det);
  $this->table->clear();

}
