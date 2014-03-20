<?php

$tmpl = array('table_open' => '<table  id="details" class="zebra">');
$this->table->set_template($tmpl);

$this->table->set_caption(lang('rr_requestawaiting'));
foreach ($userdata as $row)
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
    echo '<div class="prepend-6 span-12"><div class="error">' . $error_message . '</div></div>';
}
echo $this->table->generate();
$this->table->clear();
