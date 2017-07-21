<?php
if (!empty($success_message)) {
    echo '<div data-alert class="alert-box success">' . $success_message . '</div>';
}
if (!empty($error_message)) {
    echo '<div data-alert class="alert-box alert">' . $error_message . '</div>';

}
if (!empty($showform)) {
    $btns[] = '<a class="button alert" href="' . base_url('providers/detail/show/' . $providerid . '') . '">' . lang('rr_cancel') . '</a>';
    $btns[] = '<button type="submit" name="modify" value="submit" class="button">' . lang('rr_save') . '</button>';

    echo form_open(current_url(), array('id' => 'formver2')) .
        validation_errors('<div data-alert class="alert-box alert">', '</div>') .
        form_fieldset('Leaving federation form').
     '<div class="small-12 columns"><div class="small-3 columns"><label for="fedid" class="right inline">' . lang('rr_selectfedtoleave') . '</label></div>'.
     '<div class="small-9 medium-7 columns end">' . form_dropdown('fedid', $feds_dropdown, set_value('fedid')) . '</div></div>';

    echo '<div class="small-12 column">'.
     '<div class="small-3 columns">'.
     '<label for="message" class="right inline">' . lang('rr_message') . '</label>'.
     '</div>'.
     '<div class="small-9 medium-7 columns end">'.
     form_textarea(array('name'=>'message')).
     '</div>'.
     '</div>';
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
