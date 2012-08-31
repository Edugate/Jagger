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

<script type="text/javascript">
    $(function() {		
        $("#details").tablesorter({sortList:[[0,0],[2,1]], widgets: ['zebra']});
        $("#options").tablesorter({sortList: [[0,0]], headers: { 3:{sorter: false}, 4:{sorter: false}}});
    });	
</script>
<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$tmpl = array('table_open' => '<table  id="details" class="tablesorter drop-shadow lifted">');

    $this->table->set_template($tmpl);
    $this->table->set_heading('Name','Full name','OID','URN');
    $this->table->set_caption('List Of Attributes');
    echo $this->table->generate($attributes);
?>
