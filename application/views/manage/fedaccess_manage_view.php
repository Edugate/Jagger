<?php

if(!empty($row))
{
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
