<?php
if (!empty($error_message)) {
    echo '<div data-alert class="alert-box alert">' . $error_message . '</div>';
}
if ($showaddbutton) {

    echo '<div class="small-12 text-right"><a href="' . base_url() . 'manage/regpolicy/add" class="button small">' . lang('addregpol_btn') . '</a></div>';

}

if (!empty($rows)) {
    $tmpl = array('table_open' => '<table  id="details" class="tablesorter">');
    $this->table->set_template($tmpl);
    $this->table->set_heading(lang('rr_displayname'), lang('regpol_language'), lang('regpol_url'), lang('regpol_description'), lang('rr_status'), lang('rr_action'));
    echo $this->table->generate($rows);
    $this->table->clear();

}

echo '<div id="confirmremover" class="reveal-modal small" data-reveal>';
echo '<h3>' . lang('douwanttoproceed') . '</h3>';

echo '<div>'.lang('regpolrmstr').': <span class="data-fieldname"></span></div>';
echo '<div>'.lang('countentconnected').': <span class="data-counter"></span></div>';
echo form_open();

$btns = array(
    '<button class="button alert modal-close" value="cancel" name="cancel" type="reset">'.lang('rr_no').'</button>',
    '<a href="#" class="yes button">' . lang('rr_yes') . '</a>'
);

echo '<p><div class="buttons small-12 columns small-text-right">';

echo revealBtnsRow($btns);
        
echo '</div></p>';

echo form_close();
echo '<a class="close-reveal-modal">&#215;</a>';
echo '</div>';


echo '<div id="regpolmembers" class="reveal-modal small" data-reveal>';
echo '<h4>'.lang('modtl_listentconregpol').'</h4>';
echo '<div class="datacontent"></div>';
echo '<a class="close-reveal-modal">&#215;</a>';
echo '</div>';

