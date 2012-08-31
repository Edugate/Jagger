<?php

if(!empty($row))
{
        $tmpl = array('table_open' => '<table  id="details" class="tablesorter">');
        $this->table->set_template($tmpl);

        $this->table->set_caption('Table of access list for: '.$resource_name);
        $r = '';
        if(!empty($readlegend))
        {
            $r = showHelp($readlegend);
        } 
        $this->table->set_heading('Username','Read'.$r,'Write','Manage permissions');
        echo  $this->table->generate($row);
        $this->table->clear();


}
