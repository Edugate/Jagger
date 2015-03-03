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
    echo '<div class="medium-2 column"><label for="cron[minute]">'.lang('cronminute').'<input type="text" name="cron[minute]"  value="'. set_value('cron[minute]', $orig['jminute']).'"/></label></div>';
    echo '<div class="medium-2 column"><label for="cron[hour]">'.lang('cronhour').'<input type="text" name="cron[hour]" value="'. set_value('cron[hour]', $orig['jhour']).'"/></label></div>';
    echo '<div class="medium-2 column"><label for="cron[dom]">'.lang('crondom').'<input type="text" name="cron[dom]" value="'. set_value('cron[dom]', $orig['jdom']).'"/></label></div>';
    echo '<div class="medium-2 column"><label for="cron[month]">'.lang('cronmonth').'<input type="text" name="cron[month]" value="'. set_value('cron[month]', $orig['jmonth']).'"/></label></div>';
    echo '<div class="medium-2 column end"><label for="cron[dow]">'.lang('crondow').'<input type="text" name="cron[dow]" value="'. set_value('cron[dow]', $orig['jdow']).'"/></label></div>';
    echo '</div></div>';
    echo $rowClose;
    echo $rowOpen;
    echo $col1Open;
    echo '<label for="isenabled">'.lang('taskenabled').'?</label>';
    echo $col1Close;
    echo '<div class="medium-9 column"><input type="checkbox" name="isenabled" value="1" '.set_checkbox('isenabled', '1', $orig['jenabled']).'/></div>';
    echo $rowClose;

    echo $rowOpen;
    echo $col1Open;
    echo '<label for="comment">'.lang('rr_description').'</label>';
    echo $col1Close;
    echo '<div class="medium-9 column">';
    echo '<textarea name="comment">'.set_value('comment',$orig['comment']).'</textarea>';
    echo '</div>';
    echo $rowClose;

    echo $rowOpen;
    echo $col1Open;
    echo '<label for="istemplate">'.lang('tasktemplate').'?</label>';
    echo $col1Close;
    echo '<div class="medium-9 column"><input type="checkbox" name="istemplate" value="1" '.set_checkbox('istemplate', '1', $orig['jtemplate']).'/></div>';
    echo $rowClose;


    echo $rowOpen;
    echo $col1Open;
    echo '<label for="fnname">'.lang('rrworkerfn').'</label>';
    echo $col1Close;
    echo '<div class="medium-9 column"><input type="text" name="fnname" value="'. set_value('fnname', $orig['jcommand']).'"/></div>';
    echo $rowClose;

    echo $rowOpen;
    echo $col1Open;
    echo '<label for="params">'.lang('rrworkerfnparams').'</label>';
    echo $col1Close;
    echo '<div class="medium-9 column">';
    if($paramssubmit === null)
    {
        $i = 0;
        foreach($orig['jparams'] as $k => $v)
        {

            echo '<div class="row"><div class="small-6 column"><label>'.lang('rrparamname').'<input name="params['.$i.'][name]" type="text" value="'.html_escape($k).'"/></label></div>';
            echo '<div class="small-6 column"><label>'.lang('rrparamvalue').'<input name="params['.$i.'][value]" type="text" value="'.html_escape($v).'"/></label></div></div>';
            $i++;
        }
    }
    else
    {
        foreach($paramssubmit as $k => $v)
        {

            echo '<div class="row"><div class="small-6 column"><label>'.lang('rrparamname').'<input name="params['.$k.'][name]" type="text" value="'.html_escape($v['name']).'"/></label></div>';
            echo '<div class="small-6 column"><label>'.lang('rrparamvalue').'<input name="params['.$k.'][value]" type="text"  value="'.html_escape($v['value']).'"/></label></div></div>';
        }
    }

    echo '<div class="row"><div class="small-12 column"><button id="taskformaddparam" name="addparamval" class="secondary small">'.lang('arrparambtn').'</button></div></div>';





    echo '</div>';
    echo $rowClose;




    echo $rowOpen;
    echo '<div class="small-12 column text-center">';

    echo '<button type="submit" value="submit" name="submit" class="small"/>'.lang('rr_submit').'</button>';
    echo '</div>';
    echo $rowClose;













    echo form_close();
}