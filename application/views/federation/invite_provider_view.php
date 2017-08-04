<?php
if (!empty($error_message)) {
    echo '<div data-alert class="alert-box alert">' . $error_message . '</div>';
}
if (!empty($providers)) {

    echo form_open();
    echo '<fieldset class="fieldset small-12 medium-10 column"><legend>'.lang('rr_fedinvidpsp').'</legend>';
    $errors_v = validation_errors('<div>', '</div>');
    if (!empty($errors_v)) {
        echo '<div data-alert class="alert-box alert">';
        echo $errors_v;
        echo "</div>";
    }
    echo '<div class="small-12 columns">';
    echo '<div class="small-12 medium-3 columns">' . jform_label('' . lang('rr_select') . '', 'provider') . '</div>';
    echo '<div class="small-12 medium-7 columns end">' . form_dropdown('provider', $providers, set_value('provider'),'class="select2"') . '</div>';
    echo "</div>";
    echo '<div class="small-12 columns">';
    echo '<div class="small-12 medium-3 columns">' . jform_label('' . lang('rr_message') . '', 'message') . '</div>';
    echo '<div class="small-12 medium-7 columns end">' . form_textarea('message') . '</div>';
    echo "</div>";
    $buttons = array(
        '<a href="'.$fedurl.'" class="button alert">'.lang('rr_cancel').'</a>',
        '<button type="submit" name="submit" value="Invitation" class="button savebutton saveicon">'.lang('rr_submit').'</button>',
    );
    echo '</fieldset>';
    echo '<div class="small-12 column">';
    echo '<div class="small-12 medium-3 column"></div>';
    echo '<div class="small-12 medium-10 column end">';
    echo revealBtnsRow($buttons);
    echo '</div>';
    echo '</div>';
    echo form_close();
}
