<?php

if (empty($list))
{
    $error_message = lang('rerror_nothinginqueue');
}
if (!empty($message))
{
    echo '<div data-alert class="alert-box info"><p>' . $message . '</p></div>';
}
if (!empty($error_message))
{
    echo '<div data-alert class="alert-box warning">' . $error_message . '</div>';
}
if (!empty($list))
{
    $tmpl = array('table_open' => '<table  id="details" class="tablesorter">');
    $this->table->set_template($tmpl);
    $this->table->set_heading(lang('rr_tbltitle_date'), lang('rr_tbltitle_requester'), lang('rr_tbltitle_requesttype'), lang('rr_tbltitle_primcontact'), lang('rr_tbltitle_name'), lang('rr_tbltitle_confirmed'), '');
    $this->table->set_caption(lang('rr_listawaiting'));
    foreach ($list['q'] as $q)
    {
        if ($q['confirmed'])
        {
            $confirm = lang('rr_yes');
        }
        else
        {
            $confirm = lang('rr_no');
        }
        $cdate = $q['idate'];
        $detail = anchor(base_url() . "/reports/awaiting/detail/" . $q['token'], '<i class="fi-arrow-right"></i>');
        $this->table->add_row($q['idate'], $q['requesterCN'] ."(". $q['requester'] .")", $q['type'] . " - " . $q['action'], $q['mail'], $q['iname'], $confirm, $detail);
    }
    echo $this->table->generate();
    $this->table->clear();
    if (count($list['s']) > 0)
    {
        $tmpl = array('table_open' => '<table  id="details" class="tablesorter">');
        $this->table->set_template($tmpl);
        $this->table->set_caption(lang('notificationsapprovelist'));
        $this->table->set_heading(lang('rr_username'),lang('subscrtype'),'');
        
        foreach ($list['s'] as $s)
        {
          $this->table->add_row( $s['subscriber'] ,$s['type'],'<a href="'.base_url().'notifications/subscriber/mysubscriptions/'.base64url_encode($s['subscriber']).'"><i class="fi-arrow-right"></i></a>');
        }
        echo $this->table->generate();
    $this->table->clear();
    }
}
