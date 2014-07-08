<?php

$tmpl = array('table_open' => '<table  id="details" class="zebra responsive">');
$this->table->set_template($tmpl);

$this->table->set_caption(lang('rr_requestawaiting'));
foreach ($provider as $row)
{

    if (array_key_exists('header', $row))
    {
        $cell = array('data' => $row['header'], 'class' => 'highlight', 'colspan' => 2);
        $this->table->add_row($cell);
    }
    elseif (array_key_exists('2cols', $row))
    {
        $cell = array('data' => $row['2cols'], 'colspan' => 2);
        $this->table->add_row($cell);
    }
    else
    {
        $this->table->add_row($row['name'], $row['value']);
    }
}
if (!empty($error_message))
{
    echo '<div data-alert class="alert-box alert">' . $error_message . '</div>';
}
echo '<div class="row">';
echo $this->table->generate();
echo '</div>';
$this->table->clear();
