<?php
if(!empty($d) && is_array($d))
{
   $tmpl = array ( 'table_open'  => '<table id="details" class="zebra">' );
   $this->table->set_template($tmpl);
   foreach ($d as $row)
   {
       if(array_key_exists('header', $row))
       {
           $cell = array('data' => $row['header'], 'class' => 'highlight', 'colspan' => 2);
           $this->table->add_row($cell);
       }
       elseif(array_key_exists('2cols',$row))
       {
          $cell = array('data' => $row['2cols'], 'colspan' => 2);
          $this->table->add_row($cell);
       }
       else
       {
         if(isset($row['name']))
       {
           $c1 = &$row['name'];
       }
       else
       {
           $c1 = '';
       }
       if(isset( $row['value']))
       {
           $c2 = &$row['value'];
       }
       else
       {
           $c2 = '';
       }
       $this->table->add_row($c1, $c2);
       }
    
   }
   echo $this->table->generate();
   $this->table->clear();
}
