<?php

if(!empty($row))
{
        echo '<div id="pagetitle">'.lang('rr_accessmngmt').'</div>';
        echo '<div id="subtitle"><h3>'.anchor(base_url().'providers/detail/show/'.$resourceid,$resourcename).'</h3><h4>'.$entityid.'</h4></div>';
        $tmpl = array('table_open' => '<table  id="details" class="tablesorter">');
        $this->table->set_template($tmpl);

        $r = '';
        if(!empty($readlegend))
        {
            $r = showHelp($readlegend);
        } 
        $this->table->set_heading(''.lang('rr_username').'',''.lang('rr_read').$r.'',''.lang('rr_write').'',''.lang('rr_mngmtperm').'');
        echo  $this->table->generate($row);
        $this->table->clear();


}
