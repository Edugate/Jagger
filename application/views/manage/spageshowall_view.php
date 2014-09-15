<?php
if(!empty($msg1) && !empty($msg2))
{
    echo '<div class="small-12 column">';
    
    echo '<div data-alert class="alert-box warning">';
    echo $msg1. ' '.$msg2.': front_page';
    echo '</div>';
    echo '</div>';
}
echo '<div class="small-12 column right"><div class="right"> '.$addbtn.'</div></div>';



echo '<div class="small-12 column">';
$tmpl = array ('table_open' => '<table role="grid">');
$this->table->set_template($tmpl);
$this->table->set_heading($rowsHeading);
echo $this->table->generate($rows);
$this->table->clear();
echo '</div>';