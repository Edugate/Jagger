<?php

$this->load->library('table');
//$this->table->set_heading('Name');
foreach($templgroups as $t)
{
    $this->table->add_row(array(
      ''.lang($t['desclang']).''
            ));
    
    if(isset($t['data']))
    {
        foreach($t['data'] as $v)
        {
            $this->table->add_row(array(
                $v->getGroup()
            ));
        }
    }
}
echo $this->table->generate();