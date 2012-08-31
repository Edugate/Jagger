<script type="text/javascript">
    $(function() {		
        $("#details").tablesorter({sortList:[[0,0],[2,1]], widgets: ['zebra']});
        $("#options").tablesorter({sortList: [[0,0]], headers: { 3:{sorter: false}, 4:{sorter: false}}});
    });	
</script>
<?php
$tmpl = array('table_open' => '<table  id="details" class="tablesorter drop-shadow lifted">');
$this->table->set_template($tmpl);
$this->table->set_heading('','Name','URN','Public','External/Local','Description');
$this->table->set_caption('Federations list in the system');
echo $this->table->generate($fedlist);
$this->table->clear();
