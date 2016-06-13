<?php
if (empty($list))
{
    $error_message = lang('rr_noawaitingforapproval');
}
if (!empty($message))
{
    echo '<p>' . $message . '</p>';
}
if (!empty($error_message))
{
    echo '<span>' . $error_message . '</span>';
}
if (!empty($list))
{
  
    $tmpl = array('table_open' => '<table  id="detailsi" class="itablesorter">');
    $this->table->set_template($tmpl);
    $this->table->set_heading(lang('rr_tbltitle_date'), lang('rr_tbltitle_requester'), lang('rr_tbltitle_requesttype') ,'');
    foreach ($list['q'] as $q)
    {
        if ($q['confirmed'])
        {
            $confirm = lang('rr_yes');
        } else
        {
            $confirm = lang('rr_no');
        }
        $cdate = $q['idate'];
        $detail = anchor(base_url('reports/awaiting/detail/'.$q['token'].'') , '<i class="fa fa-arrow-right"></i>');
        $this->table->add_row($q['idate'], $q['requesterCN'] ." (". $q['requester'] .")", $q['type'] . " - " . $q['action'], $detail);
    }
    echo $this->table->generate();
    $this->table->clear();
    //@todo add list notification approval request
}
