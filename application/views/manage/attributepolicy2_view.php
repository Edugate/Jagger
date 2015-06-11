<?php




echo '
<ul class="tabs" data-tab role="tablist">
  <li class="tab-title active" role="presentational" ><a href="#panel2-1" role="tab" tabindex="0" aria-selected="true" controls="panel2-1">' . lang('rr_attributes') . '</a></li>
  <li class="tab-title" role="presentational" ><a href="#panel2-2" role="tab" tabindex="0"aria-selected="false" controls="panel2-2">' . lang('defaultpolicytab') . '</a></li>
  <li class="tab-title" role="presentational"><a href="#panel2-3" role="tab" tabindex="0" aria-selected="false" controls="panel2-3">' . lang('fedspolicytab') . '</a></li>
  <li class="tab-title" role="presentational" ><a href="#panel2-4" role="tab" tabindex="0" aria-selected="false" controls="panel2-4">' . lang('ecpolicytab') . '</a></li>
  <li class="tab-title" role="presentational" ><a href="#panel2-5" role="tab" tabindex="0" aria-selected="false" controls="panel2-5">' . lang('sppolicytab') . '</a></li>
</ul>
';
?>


<div class="tabs-content">
    <section role="tabpanel" aria-hidden="false" class="content active" id="panel2-1">
        <?php

        if (!empty($arpgenresult)) {
            echo '<pre>';
            print_r($arpgenresult);
            echo '</pre>';
        }
        ?>
    </section>
    <section role="tabpanel" aria-hidden="true" class="content" id="panel2-2">
        <h2>Second panel content goes here...</h2>
    </section>
    <section role="tabpanel" aria-hidden="true" class="content" id="panel2-3">
        <h2>Third panel content goes here...</h2>
    </section>
    <section role="tabpanel" aria-hidden="true" class="content" id="panel2-4">
        <h2>Fourth panel content goes here...</h2>
    </section>
    <section role="tabpanel" aria-hidden="true" class="content" id="panel2-5">
        <h2>Fourth panel content goes here...</h2>
    </section>
</div>