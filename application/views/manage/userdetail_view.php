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
echo '<section role="tabpanel" aria-hidden="true" class="content" id="' . $tabs[3]['tabid'] . '"> ' . $this->table->generate($tabs[3]['tabdata']) . '  </section> </div>';
$this->table->clear();
echo '<div id="managerole" class="reveal-modal small" data-reveal style="display: none">';
echo '<h2>This is a modal.</h2>';
echo '<a class="close-reveal-modal">&#215;</a>';
echo '</div>';
