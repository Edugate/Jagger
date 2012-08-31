<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$tmpl = array('table_open' => '<table  id="details" class="zebra">');

$this->table->set_template($tmpl);
$this->table->set_heading('Name', 'Helpdesk URL');
$this->table->set_caption('Service Providers List (found '.$sps_count.')');
echo $this->table->generate($sprows);
?>
