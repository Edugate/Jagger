<?php
  $tmpl = array ( 'table_open'  => '<table  id="detailsnosort" class="zebra">' );
  $this->table->set_heading(''.lang('rr_tbltitle_name').'',''.lang('rr_details').'');
  $this->table->set_template($tmpl);
  echo $this->table->generate($det);
  $this->table->clear();

  echo '<div id="managerole" class="reveal-modal small" data-reveal style="display: none">';
  echo '<h2>This is a modal.</h2>';
  echo 'dsfsdfsdf';
  echo '<a class="close-reveal-modal">&#215;</a>';
  echo '</div>';
