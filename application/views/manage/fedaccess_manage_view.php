<?php

if(!empty($row))
{
        echo '<div id="pagetitle">'.lang('rr_federation').' '.strtolower(lang('rr_accessmngmt')).'</div>';
        echo '<div id="subtitle"><h3>'.anchor($fedlink,$resourcename).'</h3></div>';
        $tmpl = array('table_open' => '<table  id="details">');
        $this->table->set_template($tmpl);

        $r = '';
        if(!empty($readlegend))
        {
            $r = showBubbleHelp($readlegend);
        } 
        $this->table->set_heading(''.lang('rr_username').'',''.lang('rr_read').$r,''.lang('rr_write').'',''.lang('rr_mngmtperm').'');
        echo  $this->table->generate($row);
        $this->table->clear();


}
