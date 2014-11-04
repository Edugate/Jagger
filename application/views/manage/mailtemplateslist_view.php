<?php

$colspan = 6;
foreach ($templgroups as $t)
{
    
    $groupcell = array('data'=>lang($t['desclang']), 'colspan'=>$colspan, 'class'=>'section');

    $this->table->add_row($groupcell);
    
    if (isset($t['data']) && count($t['data'])>0)
    {
        $i = 1;
        foreach ($t['data'] as $v)
        {
            $labels = '';
            if($v->isEnabled())
            {
                $labels .= '<span class="label">enabled</span>';
            }
            else
            {
                $labels .= '<span class="label warning">disabled</span>';
            }
            if($v->isDefault())
            {
                $labels .= ' <span class="label">defaul</span>';
            }
            $edit = '<a href="'.base_url().'manage/mailtemplates/edit/'.$v->getId().'"><i class="fi-pencil"></i></a>';
            $r = array($i++, $v->getLanguage(),$v->getSubject(),  nl2br($v->getBody()),$labels, $edit);
            
            $this->table->add_row($r);
        }
    }
    else
    {
        $groupcelldet = array('data'=>'no mail templates found for the group', 'colspan'=>$colspan, 'class'=>'warning');
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
