<?php
$this->load->library('table');

$this->table->set_heading('cron',lang('rr_description'),lang('rrworkerfn'),lang('rrworkerfnparams'),'isdue',lang('rrlastrun'),lang('rrnextrun'),lang('rr_status'),'');

if($rows)
{
    echo $this->table->generate($rows);
}
