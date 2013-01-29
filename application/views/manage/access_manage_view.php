<?php

if(!empty($row))
{
        echo '<div id="subtitle"><h3>Identity Provider:&nbsp;'.$resource_name.'</h3></div>';
        $tmpl = array('table_open' => '<table  id="details" class="tablesorter">');
        $this->table->set_template($tmpl);

        $r = '';
        if(!empty($readlegend))
        {
            $r = showHelp($readlegend);
        } 
        $this->table->set_heading('Username','Read'.$r,'Write','Manage permissions');
        echo  $this->table->generate($row);
        $this->table->clear();


}
