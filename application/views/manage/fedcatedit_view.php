<?php
$errors_v = validation_errors('<div class="error">', '</div>');
if (!empty($errors_v)) {
    echo '<div class="error">';
    echo $errors_v;
    echo "</div>";
}
if (!empty($success_message)) {
    echo '<div  data-alert class="alert-box success">' . $success_message . '</div>';

}
echo '<div class="small-12  column">';
echo form_open();
echo '<fieldset class="fieldset"><legend>' . lang('general') . '</legend><div class="small-12 columns">';
echo '<div class="small-3 columns">';
echo '<label for="buttonname" class="right inline">' . lang('tbl_catbtnname') . '</label></div>';
echo '<div class="small-6 large-7 columns end"><input type="text" id="buttonname" name="buttonname" 
                           value="' . $buttonname . '" /></div>';
echo '</div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns"><label for="fullname" class="right inline">' . lang('tbl_catbtnititlename') . '</label></div>';
echo '<div class="small-6 large-7 columns end"><input type="text" id="fullname" name="fullname" 
                           value="' . $fullname . '" /></div>';
echo '</div>';
echo '<div  class="small-12 columns">';
echo '<div class="small-3 columns"><label for="description"  class="right inline">' . lang('rr_description') . '</label></div>';
echo '<div class="small-6 large-7 columns end"><textarea id="description" name="description" rows="5">';
echo $description;
echo '</textarea></div>';

echo '</div>';
echo '</fieldset>';
echo '<fieldset><legend>' . lang('rrfedcatmembers') . '</legend>';
echo '<span style="display: none"><input type="hidden" name="fed[controlkey]" id="fed[controlkey]" value="0"/></span>';
foreach ($multi as $m) {
    $c = false;
    if ($m['member']) {
        $c = true;
    }

    $fedid = $m['fedid'];
    $data = array(
        'name' => 'fed[]',
        'id' => 'fed[]',
        'value' => '' . $fedid . '',
        'checked' => $c,
        'class' => 'inline right',
    );
    echo '<div class="small-12 columns"><div class="small-1 medium-2 large-3 columns text-right">' . form_checkbox($data) . '</div><div class="small-9 medium-10 large-6 columns end">' . $m['fedname'] . '</div></div>';

}

echo '</fieldset>';
$btns = array(
    '<button type="submit" id="rmfedcategory" name="formsubmit" value="remove" class="button alert">' . lang('rr_remove') . '</button>',
    '<button type="submit" name="formsubmit" value="update" class="button">' . lang('rr_save') . '</button>'
);
echo revealBtnsRow($btns);
echo form_close();
echo '</div>';
echo confirmDialog('' . lang('title_confirm') . '', '' . sprintf(lang('douwanttoremove'), lang('fedcategory')) . '', '' . lang('rr_yes') . '', '' . lang('rr_no') . '');
