<?php


$errors_v = validation_errors('<div>', '</div>');
if (!empty($error_message) || !empty($errors_v))
{
    echo '<div data-alert class="alert-box alert">';
    if (!empty($error_message))
    {
        echo $error_message;
    }
    if (!empty($errors_v))
    {
        echo $errors_v;
    }
    echo '</div>';
}

if (!empty($success_message))
{
    echo '<div data-alert class="alert-box success">' . $success_message . '</div>';
}
if ($showform)
{
    $attributes = array('class' => 'span-16', 'id' => 'formver1');
    echo form_open(current_url(), $attributes);
    echo '<div data-alert class="alert-box info text-center">'.$entityid.'</div>';
    echo '<div class="small-12 column">';
    echo '<div class="medium-3 column">';
    $in = array('name' => 'entity', 'id' => 'entity');
    echo '<label for="entity" class="medium-text-right inline">'.lang('rr_plsenterentityid').'</label>';
    echo '</div>';
    echo '<div class="medium-9 column">';
    echo form_input($in);
    echo '</div>';
    echo '</div>';
    $btns = array(
        '<a href="'.$providerurl.'" class="button alert">'.lang('rr_cancel').'</a>',
        '<button name="submit" type="submit" id="submit" value="Remove" class="resetbutton deleteicon">' . lang('rr_btn_rmprovider') . '</button>');
    echo revealBtnsRow($btns);

    echo form_close();
}
