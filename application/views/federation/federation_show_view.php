<?php
if (empty($federation_is_active)) {
    echo '<div data-alert class="alert-box alert fedstatusinactive">' . lang('rr_fed_inactive_full') . '</div>';
} else {
    echo '<div data-alert class="alert-box alert fedstatusinactive hidden">' . lang('rr_fed_inactive_full') . '</div>';
}
echo '<div id="ifedtabs">';
echo '<ul class="tabs" data-tab>
 <li class="tab-title active"><a href="#general">' . lang('tabgeneral') . '</a></li>
 <li class="tab-title"><a href="#membership">' . lang('tabMembership') . '</a></li>
 <li class="tab-title"><a href="#metadata">' . lang('rr_metadata') . '</a></li>
 <li class="tab-title"><a href="#attrs">' . lang('tabAttrs') . '</a></li>';
if (!empty($fvalidator)) {
    echo '<li class="tab-title"><a href="#fvalidators">' . lang('tabFvalidators') . '</a></li>';
}
echo '<li class="tab-title"><a href="#management">' . lang('tabMngt') . '</a></li>';

echo '</ul>';

$tmpl = array('table_open' => '<table id="detailsnosort" >');
$tmpl2 = array('table_open' => '<table style="border: 0px">');
echo '<div class="tabs-content">';

echo '<div id="general" class="content active nopadding">';

if (!empty($fedpiechart)) {
    $this->table->set_template($tmpl2);
    echo '<div class="row">';
    echo '<div class="medium-8 column">';
    echo $this->table->generate($result['general']);
    echo '</div>';
    echo '<div class="medium-4 column">';
    echo $fedpiechart;
    echo '</div>';
    echo '</div>';
} else {
    $this->table->set_template($tmpl);
    echo $this->table->generate($result['general']);
}

$this->table->clear();
echo '</div>';
unset($result['general']);
/////////////////////



foreach ($result as $k => $v) {

    echo '<div id="' . $k . '" class="content nopadding">';
    $this->table->set_template($tmpl);
    echo $this->table->generate($v);
    $this->table->clear();
    echo '</div>';

}
echo '</div>';
echo '</div>';

echo confirmDialog('' . lang('title_confirm') . '', '' . lang('douwanttoproceed') . ':', '' . lang('rr_yes') . '', '' . lang('rr_no') . '');

if (!empty($hiddenspan)) {
    echo $hiddenspan;
}
?>
<div class="metadataresult" style="display: none"></div>
