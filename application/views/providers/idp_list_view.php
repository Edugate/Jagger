<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$tmpl = array('table_open' => '<table  id="details" class="zebra drop-shadow lifted">');

$this->table->set_template($tmpl);
$this->table->set_heading('Name (entityID)','Registration Date', 'Helpdesk Url');
$this->table->set_caption('List Of Identity Providers (found ' . $idps_count . ')');
echo $this->table->generate($idprows);



