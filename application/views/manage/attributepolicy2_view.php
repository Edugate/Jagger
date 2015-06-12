<?php




echo '
<ul class="tabs" id="attrpolstab" data-tab role="tablist">
  <li class="tab-title active" role="presentational" data-reveal-ajax-tab="https://janul.no-ip.info/rr3/providers/detail/showlogs/3"><a href="#attrpol-1" role="tab" tabindex="0" aria-selected="true" controls="attrpol-1">' . lang('rr_attributes') . '</a></li>
  <li class="tab-title" role="presentational"><a href="#attrpol-2" role="tab" tabindex="0"aria-selected="false" controls="attrpol-2">' . lang('defaultpolicytab') . '</a></li>
  <li class="tab-title" role="presentational"><a href="#attrpol-3" role="tab" tabindex="0" aria-selected="false" controls="attrpol-3">' . lang('fedspolicytab') . '</a></li>
  <li class="tab-title" role="presentational"><a href="#attrpol-4" role="tab" tabindex="0" aria-selected="false" controls="attrpol-4">' . lang('ecpolicytab') . '</a></li>
  <li class="tab-title" role="presentational"><a href="#attrpol-5" role="tab" tabindex="0" aria-selected="false" controls="attrpol-5">' . lang('sppolicytab') . '</a></li>
</ul>
';
?>


<div id="attrpols" class="tabs-content">
    <section role="tabpanel" aria-hidden="false" class="content active" id="attrpol-1">
        <?php

            echo '<pre>';
            print_r($arpsupport);
            echo '</pre>';

        ?>
    </section>
    <section role="tabpanel" aria-hidden="true" class="content" id="attrpol-2">
        <h2>Second panel content goes here...</h2>
    </section>
    <section role="tabpanel" aria-hidden="true" class="content" id="attrpol-3">
        <h2>Third panel content goes here...</h2>
    </section>
    <section role="tabpanel"  aria-hidden="true" class="content" id="attrpol-4">
        <h2>Fourth panel content goes here...</h2>
    </section>
    <section role="tabpanel" aria-hidden="true" class="content" id="attrpol-5">
        <h2>Fifth panel content goes here...</h2>
    </section>
</div>