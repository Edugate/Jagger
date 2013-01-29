<div id="subtitle"><h3>Federations list in the system</h3></div>

<script type="text/javascript">
$(function() {    
  $("#details").tablesorter({sortList:[[0,0],[2,1]], widgets: ['zebra']});
  $("#options").tablesorter({sortList: [[0,0]], headers: { 3:{sorter: false}, 4:{sorter: false}}});
 });  
</script>
<?php
$tmpl = array('table_open' => '<table  id="details" class="tablesorter drop-shadow lifted">');
$this->table->set_template($tmpl);
$this->table->set_heading('Name','URN','','Description','#');
echo $this->table->generate($fedlist);
$this->table->clear();
?>
