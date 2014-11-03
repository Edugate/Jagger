<?php


foreach ($templgroups as $t)
{
    
    $groupcell = array('data'=>lang($t['desclang']), 'colspan'=>1, 'class'=>'section');

    $this->table->add_row($groupcell);
    
    if (isset($t['data']) && count($t['data'])>0)
    {
        foreach ($t['data'] as $v)
        {
            $this->table->add_row(array(
                $v->getGroup()
            ));
        }
    }
    else
    {
        $groupcelldet = array('data'=>'no mail templates found for the group', 'colspan'=>1, 'class'=>'warning');
        $this->table->add_row($groupcelldet);
    }
}

if (!empty($showaddbtn) && $showaddbtn === TRUE)
{
    echo '<div class="small-12 columns text-right"><a href="' . base_url() . 'manage/mailtemplates/edit" class="button small addbutton addicon">' . lang('rr_add') . '</a></div>';
}
echo '<div class="small-12 columns">';
echo $this->table->generate();
echo '</div>';
