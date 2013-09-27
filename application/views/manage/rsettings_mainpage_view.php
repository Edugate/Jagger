<div id="pagetitle"><?php echo lang('title_rsettings');?></div>


<?php

if(!empty($tobesynced))
{
     echo '<h3><div class="alert"><p>'.lang('warn_incosistencysettings').'</p></div><a href="'.base_url().'manage/rsettings/synchronize" id="synchsettings"><button class="btn" type="button">'.lang('btnsynch').'</button></a><h3>';
}
?>
<div id="syncresult"></div>
<?php

$tmpl = array('table_open' => '<table  id="details" class="tablesorter">');
$this->table->set_template($tmpl);
$this->table->set_heading('fullname','value','desc','enabled/disabled');
echo $this->table->generate($tdata);
$this->table->clear();






