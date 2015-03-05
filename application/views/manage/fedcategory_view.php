<?php
if(!empty($showaddbtn) && $showaddbtn === TRUE)
{
   echo '<div class="small-12 columns text-right"><a href="'.base_url().'manage/fedcategory/addnew" class="button small addbutton addicon">'.lang('addfedcat_btn').'</a></div>';
}
echo '<div class="small-12 columns">';
if(count($result) > 0)
{
  $tmpl = array('table_open' => '<table  id="detailsnosort" class="tablesorter">');
  $this->table->set_template($tmpl);
  $this->table->set_heading(''.lang('tbl_catbtnname').'',''.lang('tbl_catbtnititlename').'',''.lang('rr_description').'',''.lang('rr_action').'');
  echo $this->table->generate($result);
}
else
{
  echo '<div data-alert class="alert-box warning">'.lang('nocatsfound').'</div>';
}
echo '</div>';
