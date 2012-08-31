<?php
$tmpl = array('table_open'=>'<table id="aregister" class="zebra">');
$this->table->set_template($tmpl);
$this->table->set_heading('Name','entityID','');
echo "<h2>Members of \"".$federation_name."\" federation</h2>"; 
echo "<span>".anchor($metadata_link, 'Display metadata in xml format')."</span><br />";
echo $this->table->generate($m_list);
