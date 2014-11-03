<?php

echo form_open();
if ($newtmpl === TRUE)
{
    echo '<div class="small-12 columns">';
    echo jGenerateDropdown(lang('mtmplgroup'),'msggroup',$groupdropdown,set_value('msggroup', ''),'');
    echo '</div>';
}
echo '<div class="small-12 columns">';
echo jGenerateInput(lang('mtmplsbj'), 'msgsubj', set_value('msgsubj', $msgsubj), '', null);
echo '</div>';
echo '<div class="small-12 columns">';
echo jGenerateTextarea(lang('mtmplbody'), 'msgbody', set_value('msgbody', $msgbody), '');
echo '</div>';
echo form_close();

