<div id="pagetitle"><?php echo lang('ent_list_title');?></div>

<?php
if(!empty($error_message))
{
    echo '<div class="error">'.$error_message.'</div>';
}

if(!empty($rows))
{
     $tmpl = array('table_open' => '<table  id="details" class="tablesorter">');
     $this->table->set_template($tmpl);
     $this->table->set_heading(lang('entcat_shortname'),lang('entcat_url'),lang('entcat_description'),'#');
     echo $this->table->generate($rows);
     $this->table->clear();    

}
