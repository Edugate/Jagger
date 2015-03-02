<?php

$rowOpen = '<div class="small-12 column">';
$col1Open = '<div class="medium-3 column medium-text-right">';
$col1Close = '</div>';
$rowClose = '</div>';

echo validation_errors('<span class="error">', '</span>');
if(!empty($formdata))
{
    echo form_open();
    echo $rowOpen;

    echo $col1Open;
    echo '';
    echo $col1Close;
    echo '<div class="medium-9 column"><div class="row">';
    echo '<div class="medium-2 column"><label for="cron[minute]">Minute<input type="text" name="cron[minute]"  value="'. set_value('cron[minute]', $orig['jminute']).'"/></label></div>';
    echo '<div class="medium-2 column"><label for="cron[hour]">Hour<input type="text" name="cron[hour]" value="'. set_value('cron[hour]', $orig['jhour']).'"/></label></div>';
    echo '<div class="medium-2 column"><label for="cron[dom]">Day of month<input type="text" name="cron[dom]" value="'. set_value('cron[dom]', $orig['jdom']).'"/></label></div>';
    echo '<div class="medium-2 column"><label for="cron[month]">Month<input type="text" name="cron[month]" value="'. set_value('cron[month]', $orig['jmonth']).'"/></label></div>';
    echo '<div class="medium-2 column end"><label for="cron[dow]">Day of week<input type="text" name="cron[dow]" value="'. set_value('cron[dow]', $orig['jdow']).'"/></label></div>';
    echo '</div></div>';
    echo $rowClose;
    echo $rowOpen;
    echo $col1Open;
    echo '<label for="isenabled">Task enabled?</label>';
    echo $col1Close;
    echo '<div class="medium-9 column"><input type="checkbox" name="isenabled" value="1" '.set_checkbox('isenabled', '1', $orig['jenabled']).'/></div>';
    echo $rowClose;

    echo $rowOpen;
    echo $col1Open;
    echo '<label for="comment">Comment</label>';
    echo $col1Close;
    echo '<div class="medium-9 column">';
    echo '<textarea name="comment">'.set_value('comment',$orig['comment']).'</textarea>';
    echo '</div>';
    echo $rowClose;

    echo $rowOpen;
    echo $col1Open;
    echo '<label for="istemplate">Is it template?</label>';
    echo $col1Close;
    echo '<div class="medium-9 column"><input type="checkbox" name="istemplate" value="1" '.set_checkbox('istemplate', '1', $orig['jtemplate']).'/></div>';
    echo $rowClose;


    echo $rowOpen;
    echo $col1Open;
    echo '<label for="fnname">Fn name</label>';
    echo $col1Close;
    echo '<div class="medium-9 column"><input type="text" name="fnname" value="'. set_value('fnname', $orig['jcommand']).'"/></div>';
    echo $rowClose;

    echo $rowOpen;
    echo $col1Open;
    echo '<label for="params">Params</label>';
    echo $col1Close;


    echo $rowClose;




    echo $rowOpen;
    echo '<div class="small-12 column text-center">';

    echo '<button type="submit" value="submit" name="submit" class="small"/>Submit</button>';
    echo '</div>';
    echo $rowClose;













    echo form_close();
}