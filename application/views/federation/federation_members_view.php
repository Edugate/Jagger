<?php
$tmpl = array('table_open'=>'<table id="aregister" class="zebra">');
$this->table->set_template($tmpl);
$this->table->set_heading(''.lang('rr_tbltitle_name').'',''.lang('rr_entityid').'','');
echo '<h2>'.sprintf(lang('rr_membersoffed'),$federation_name).'</h2>';
echo "<span>".anchor($metadata_link, 'Display metadata in xml format')."</span><br />";
echo $this->table->generate($m_list);
$this->table->clear();
