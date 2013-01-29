<?php
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @subpackage  Views
 * @author      Middleware Team HEAnet 
 *  
 */



?>
<div id="subtitle"><h3>Attributes definitions list</h3></div>
<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$tmpl = array('table_open' => '<table  id="details" class="tablesorter drop-shadow lifted">');

    $this->table->set_template($tmpl);
    $this->table->set_heading('Name','Full name','OID','URN');
    echo $this->table->generate($attributes);
?>
