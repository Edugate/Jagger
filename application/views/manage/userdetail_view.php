<?php
echo '
<ul class="tabs" data-tab role="tablist">
    <li class="tab-title active" role="presentational" ><a href="#' . $tabs[0]['tabid'] . '" role="tab" tabindex="0" aria-selected="true" controls="' . $tabs[0]['tabid'] . '">' . $tabs[0]['tabtitle'] . '</a></li>
    <li class="tab-title" role="presentational" ><a href="#' . $tabs[1]['tabid'] . '" role="tab" tabindex="0"aria-selected="false" controls="' . $tabs[1]['tabid'] . '">' . $tabs[1]['tabtitle'] . '</a></li>
    <li class="tab-title" role="presentational"><a href="#' . $tabs[2]['tabid'] . '" role="tab" tabindex="0" aria-selected="false" controls="' . $tabs[2]['tabid'] . '">' . $tabs[2]['tabtitle'] . '</a></li>
    <li class="tab-title" role="presentational" ><a href="#' . $tabs[3]['tabid'] . '" role="tab" tabindex="0" aria-selected="false" controls="' . $tabs[3]['tabid'] . '">' . $tabs[3]['tabtitle'] . '</a></li>
</ul>';
echo '<div class="tabs-content">';
echo '<section role="tabpanel" aria-hidden="false" class="content active" id="' . $tabs[0]['tabid'] . '"> ' . $this->table->generate($tabs[0]['tabdata']) . ' </section>';
$this->table->clear();
echo '<section role="tabpanel" aria-hidden="true" class="content" id="' . $tabs[1]['tabid'] . '"> ' . $this->table->generate($tabs[1]['tabdata']) . ' </section>';
$this->table->clear();
echo '<section role="tabpanel" aria-hidden="true" class="content" id="' . $tabs[2]['tabid'] . '"> ' . $this->table->generate($tabs[2]['tabdata']) . ' </section>';
$this->table->clear();

/// Actions Logs
echo '<section role="tabpanel" aria-hidden="true" class="content" id="' . $tabs[3]['tabid'] . '">';

/**
 * @var $actionlogs models\Tracker[]
 */
foreach($actionlogs as $ath)
{
    $subtype = $ath->getSubType();
            if ($subtype === 'modification') {
                $date = $ath->getCreated()->modify('+ ' . j_auth::$timeOffset . ' seconds')->format('Y-m-d H:i:s');
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
                $date = $ath->getCreated()->modify('+ ' . j_auth::$timeOffset . ' seconds')->format('Y-m-d H:i:s');
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
