<?php
if(!empty($success_message))
{
    echo '<div data-alert class="alert-box success">'.$success_message.'</div>';
}
if(!empty($error_message))
{
    echo '<div data-alert class="alert-box alert">'.$error_message.'</div>';

}
if(!empty($showform))
{



    $btns[] = '<button type="submit" name="modify" value="submit" class="savebutton saveicon">' . lang('rr_save') . '</button>';
    echo  form_open(current_url(), array('id' => 'formver2'));
    echo validation_errors('<span class="error">', '</span>');
    echo  form_fieldset('Leaving federation form');
    echo '<div class="small-12 columns">';
    echo '<div class="small-3 columns">';
    echo '<label for="fedid" class="right inline">' . lang('rr_selectfedtoleave') . '</label>';
    echo '</div>';
    echo '<div class="small-9 medium-7 columns end">' . form_dropdown('fedid', $feds_dropdown, set_value('fedid')) . '</div></div>';
    echo '<div class="small-12 center columns">';
    if (strcmp($providertype, 'IDP') != 0) {
        echo '<div data-alert class="alert-box warning">' . lang('rr_alertrmspecpoliciecsp') . '</div>';
    } else {
        echo '<div data-alert class="alert-box warning">' . lang('rr_alertrmspecpoliciecidp') . '</div>';
    }
    echo '</div></div>';
    echo revealBtnsRow($btns);
    echo form_fieldset_close();
    echo form_close();

}
