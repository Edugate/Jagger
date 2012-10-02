<?php
if(!empty($metadatasigner_url))
{
   echo "<div class=\"notice\">All metadata files are signed with <b><a href=\"".$metadatasigner_url."\">".$metadatasigner_url."</a></b></div>";
}
if(!empty($tarray))
{
$tmpl = array('table_open' => '<table  id="details" class="zebra">');
$this->table->set_template($tmpl);
$this->table->set_heading('','Name', 'entityID');
$this->table->set_caption('Identity Providers List');
echo $this->table->generate($tarray);
$this->table->clear();

}
if(!empty($sarray))
{
$tmpl = array('table_open' => '<table  id="details" class="zebra">');
$this->table->set_template($tmpl);
$this->table->set_heading('','Name', 'entityID');
$this->table->set_caption('Service Providers List');
echo $this->table->generate($sarray);
$this->table->clear();

}
