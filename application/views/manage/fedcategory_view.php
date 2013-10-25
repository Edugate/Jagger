<div id="pagetitle"><?php echo lang('rrfedcatslist');?></div>
<?php

if(count($result) > 0)
{
  $tmpl = array('table_open' => '<table  id="detailsnosort" class="tablesorter">');
  $this->table->set_template($tmpl);
  $this->table->set_heading(''.lang('tbl_catbtnname').'',''.lang('tbl_catbtnititlename').'',''.lang('rr_description').'');
  echo $this->table->generate($result);
}
