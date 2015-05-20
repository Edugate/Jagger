<?php
if(!empty($metadatasigner_url))
{
   echo '<div class="notice">'.lang('allmetafilessignedwith').' <b><a href="'.$metadatasigner_url.'">'.$metadatasigner_url.'</a></b></div>';
}
if(!empty($tarray))
{
echo '<div class="sectiontitle">'.lang('rr_tbltitle_listidps').'</div>';
$tmpl = array('table_open' => '<table  id="details" class="zebra">');
$this->table->set_template($tmpl);
$this->table->set_heading('',lang('rr_tbltitle_name'), 'entityID');
echo $this->table->generate($tarray);
$this->table->clear();

}
if(!empty($sarray))
{
echo '<div class="sectiontitle">'.lang('rr_tbltitle_listsps').'</div>';
$tmpl = array('table_open' => '<table  id="details" class="zebra">');
$this->table->set_template($tmpl);
$this->table->set_heading('',lang('rr_tbltitle_name'), 'entityID');
echo $this->table->generate($sarray);
$this->table->clear();

}

if(!empty($farray))
{
    echo '<div class="sectiontitle">Federations/groups of trust</div>';
$tmpl = array('table_open' => '<table  id="details" class="zebra">');
$this->table->set_template($tmpl);
$this->table->set_heading('',lang('rr_tbltitle_name'), '');
echo $this->table->generate($farray);
$this->table->clear();

}