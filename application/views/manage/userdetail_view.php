<?php
echo '
<ul class="tabs" data-tabs id="profile-tabs">
    <li class="tabs-title is-active"><a href="#' . $tabs[0]['tabid'] . '" role="tab" tabindex="0" aria-selected="true" controls="' . $tabs[0]['tabid'] . '">' . $tabs[0]['tabtitle'] . '</a></li>
    <li class="tabs-title"><a href="#' . $tabs[1]['tabid'] . '" role="tab" tabindex="0"aria-selected="false" controls="' . $tabs[1]['tabid'] . '">' . $tabs[1]['tabtitle'] . '</a></li>
    <li class="tabs-title"><a href="#' . $tabs[2]['tabid'] . '" role="tab" tabindex="0" aria-selected="false" controls="' . $tabs[2]['tabid'] . '">' . $tabs[2]['tabtitle'] . '</a></li>
    <li class="tabs-title"><a href="#tab4" role="tab" tabindex="0" aria-selected="false" controls="tab4">' . lang('actionlogs') . '</a></li>
</ul>';
echo '<div class="tabs-content" data-tabs-content="profile-tabs">';
echo '<section class="tabs-panel is-active" id="' . $tabs[0]['tabid'] . '"> ' . $this->table->generate($tabs[0]['tabdata']) . ' </section>';
$this->table->clear();
echo '<section class="tabs-panel" id="' . $tabs[1]['tabid'] . '"> ' . $this->table->generate($tabs[1]['tabdata']) . ' </section>';
$this->table->clear();
echo '<section class="tabs-panel" id="' . $tabs[2]['tabid'] . '"> ' . $this->table->generate($tabs[2]['tabdata']) . ' </section>';
$this->table->clear();

/// Actions Logs
echo '<section role="tabpanel" aria-hidden="true" class="content" id="tab4">';

/**
 * @var $actionlogs models\Tracker[]
 */
foreach($actionlogs as $ath)
{
    $subtype = $ath->getSubType();
            if ($subtype === 'modification') {
                $date = jaggerDisplayDateTimeByOffset($ath->getCreated(),jauth::$timeOffset);
                $d = unserialize($ath->getDetail());
                $dstr = '<br />';
                if (is_array($d)) {
                    foreach ($d as $k => $v) {
                        $dstr .= '<b>' . $k . ':</b><br />';
                        if (is_array($v)) {
                            foreach ($v as $h => $l) {
                                if (!is_array($l)) {
                                    $dstr .= $h . ':' . $l . '<br />';
                                } else {
                                    foreach ($l as $lk => $lv) {
                                        $dstr .= $h . ':' . $lk . '::' . $lv . '<br />';
                                    }
                                }
                            }
                        }
                    }
                }
                $detail = 'Type: ' . $ath->getResourceType() . ', name:' . $ath->getResourceName() . '  ' . $dstr;
                $this->table->add_row($date, $detail);
            } elseif ($subtype === 'create' || $subtype == 'remove') {
                $date = jaggerDisplayDateTimeByOffset($ath->getCreated(),jauth::$timeOffset);
                $detail = 'Type: ' . $ath->getResourceType() . ', name:' . $ath->getResourceName() . ' -- ' . $ath->getDetail();
                $this->table->add_row($date, $detail);
            }
}
echo $this->table->generate();
$this->table->clear();


echo '</section> </div>';
/// end Action Logs




echo '<div id="managerole" class="reveal-modal small" data-reveal style="display: none">';
echo '<h2>This is a modal.</h2>';
echo '<a class="close-reveal-modal">&#215;</a>';
echo '</div>';
