<?php

if ($isadmin) {

    echo '<div class="small-12 text-right">' .
        '<a href="' . base_url('attributes/attributes/add') . '" class="button">' . lang('addattr_btn') . '</a></div>';

}

$tmpl = array('table_open' => '<table  id="details" class="tablesorter">');


$this->table->set_template($tmpl);
$this->table->set_heading('' . lang('attrname') . '', '' . lang('attrfullname') . '', '' . lang('attrsaml2') . '', '' . lang('attrsaml1') . '','');
echo $this->table->generate($attributes);
$this->table->clear();

