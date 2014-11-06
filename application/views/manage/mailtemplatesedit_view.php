<?php

$errors = validation_errors('<div>', '</div>');
$rowStart = '<div class="small-12 columns">';
$rowEnd = '</div>';
if (!empty($errors))
{
    echo '<div class="small-12 columns">';
    echo '<div data-alert class="alert-box alert">';
    echo $errors;
    echo '</div>';
    echo '</div>';
}

echo form_open();
if ($newtmpl === TRUE)
{
    echo $rowStart;
    echo jGenerateDropdown(lang('mtmplgroup'), 'msggroup', $groupdropdown, set_value('msggroup', ''), '');
    echo $rowEnd;
    echo $rowStart;
    echo jGenerateDropdown(lang('rr_lang'), 'msglang', $langdropdown, '', '');
    echo $rowEnd;
}
else
{
    echo $rowStart;
    echo '<div class="medium-3 columns medium-text-right"><label class="inline">' . lang('mtmplgroup') . '<label></div>';
    echo '<div class="medium-8 large-7 columns end"><input type="text" readonly="readonly" value="' . $groupdropdown['' . $msggroup . ''] . '"></div>';
    echo $rowEnd;
    echo $rowStart;
     echo '<div class="medium-3 columns medium-text-right"><label class="inline">' . lang('mtmpllang') . '<label></div>';
    echo '<div class="medium-8 large-7 columns end"><input type="text" readonly="readonly" value="' . $msglang . '"></div>';
    echo $rowEnd;
}
echo $rowStart;
echo '<div class="medium-3 columns medium-text-right"><label class="" for="msgdefault">' . lang('mtmpldefault') . '<label></div>';
echo '<div class="medium-8 large-7 columns end">' . form_checkbox('msgdefault', 'yes', set_checkbox('msgdefault', 'yes', $msgdefault)) . '</div>';
echo $rowEnd;

echo $rowStart;
echo '<div class="medium-3 columns medium-text-right"><label class="" for="msgattach">' . lang('mtmplattach') . '<label></div>';
echo '<div class="medium-8 large-7 columns end">' . form_checkbox('msgattach', 'yes', set_checkbox('msgattach', 'yes', $msgattach)) . '</div>';
echo $rowEnd;


echo $rowStart;
echo '<div class="medium-3 columns medium-text-right"><label class="" for="msgenabled">' . lang('mtmplenabled') . '<label></div>';
echo '<div class="medium-8 large-7 columns end">' . form_checkbox('msgenabled', 'yes', set_checkbox('msgenabled', 'yes', $msgenabled)) . '</div>';
echo $rowEnd;

echo $rowStart;
echo jGenerateInput(lang('mtmplsbj'), 'msgsubj', set_value('msgsubj', $msgsubj), '', null);
echo $rowEnd;
echo $rowStart;
echo '<div class="medium-8 columns medium-centered  panel callout" style="font-size: smaller">';
foreach($mailtmplGroups as $k=>$v)
{
    echo '<div><b>'.lang($v['desclang']).'</b></div>';
    echo '<div>';
    foreach($v['args'] as $a)
    {
        echo '_'.$a.'_ , ';
    }
    echo '</div>';
}
echo '</div>';
echo $rowEnd;

echo $rowStart;
echo jGenerateTextarea(lang('mtmplbody'), 'msgbody', set_value('msgbody', $msgbody), '');

echo $rowEnd;



echo '<div class="small-12 columns text-right">';
echo '<div class="medium-10 columns end"><a href="'.base_url().'manage/mailtemplates/showlist" class="button alert">'.lang('rr_cancel').'</a> <button type="submit">'.lang('rr_save').'</button>';
echo $rowEnd;

echo form_close();

