<div id="pagetitle"><?php echo lang('rrfedcatslist');?></div>
<?php
echo '<div style="width-min: 100%; text-align: right; margin-right: 0px" class="buttons"><a href="'.base_url().'manage/fedcategory/addnew"><button class="addbutton addicon">'.lang('rr_add').'</button></a></div>';


if(count($result) > 0)
{
  $tmpl = array('table_open' => '<table  id="detailsnosort" class="tablesorter">');
  $this->table->set_template($tmpl);
  $this->table->set_heading(''.lang('tbl_catbtnname').'',''.lang('tbl_catbtnititlename').'',''.lang('rr_description').'');
  echo $this->table->generate($result);
}
else
{
  echo '<div class="alert">'.lang('nocatsfound').'</div>';
}
