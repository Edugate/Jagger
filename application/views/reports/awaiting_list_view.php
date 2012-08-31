<script type="text/javascript">
    $(function() {		
        $("#details").tablesorter({sortList:[[0,0],[2,1]], widgets: ['zebra']});
        $("#options").tablesorter({sortList: [[0,0]], headers: { 3:{sorter: false}, 4:{sorter: false}}});
    });	
</script>


<?php
if (empty($list))
{
    $error_message = "Nothing is wating for approval";
}
if (!empty($message))
{
    echo "<p>" . $message . "</p>";
}
if (!empty($error_message))
{
    echo "<span class=\"alert\">" . $error_message . "</span>";
}
if (!empty($list))
{
    $tmpl = array('table_open' => '<table  id="details" class="tablesorter">');
    $this->table->set_template($tmpl);
    $this->table->set_heading('Date', 'Requester', 'Request Type', 'Primary contact', 'Name', 'Confirmed?', '');
    $this->table->set_caption('Awating list for approval');
    foreach ($list as $q)
    {
        if ($q['confirmed'])
        {
            $confirm = "yes";
        } else
        {
            $confirm = "no";
        }
        $cdate = $q['idate'];
        $detail = anchor(base_url()."/reports/awaiting/detail/" . $q['token'], '>>');
        $this->table->add_row($q['idate'], $q['requester'], $q['type'] . " - " . $q['action'], $q['mail'], $q['iname'], $confirm, $detail);
    }
    echo $this->table->generate();
}
