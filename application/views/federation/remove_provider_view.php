<?php
if(!empty($subtitle))
{
   echo '<div id="subtitle" class="span-24">'.$subtitle.'</div>';
}

if(!empty($success_message))
{
    echo '<div data-alert class="alert-box success">'.$success_message.'</div>';
}

if(!empty($error_message))
{
    echo '<div data-alert class="alert-box alert">'.$error_message.'</div>';
}
if(!empty($providers))
{

    echo form_open();
    $errors_v = validation_errors('<div">', '</div>');
    if (!empty($errors_v)) {
        echo '<div data-alert class="alert-box alert">';
        echo $errors_v;
        echo "</div>";
    }

    echo '<fieldset class="fieldset"><legend>'.lang('rmprovfromfed').': '.$fedname.'</legend>';

    echo '<div class="small-12 column">';

    echo '<div class="medium-3 column medium-text-right">';
    echo form_label(lang('rrprovs'),'provider');
    echo '</div>';
    echo '<div class="medium-9 column">';
    $dropdown = 'class="select2"';
    echo form_dropdown('provider',$providers,set_value('provider'),$dropdown);
    echo '</div>';
    echo '</div>';




    echo '<div class="small-12 column">';
    echo '<div class="medium-3 column medium-text-right">';
    echo form_label(lang('rr_message'),'message');
    echo '</div>';
    echo '<div class="medium-9 column">';
    echo form_textarea('message');
echo '</div>';
    echo '</div></fieldset>';


    $btns = array(
        '<a href="'.base_url('federations/manage/show/'.$encodedfedname.'').'" class="button alert">'.lang('rr_cancel').'</a>',
        '<button type="submit" name="submit" value="Remove" class="button">'.lang('rr_submit').'</button>'

    );
?>
<div class="buttons">
    <?php
    echo revealBtnsRow($btns);
    ?>
</div>
<?php
    echo form_close();
}
