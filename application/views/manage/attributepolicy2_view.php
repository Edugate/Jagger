<?php




echo '
<ul class="tabs" id="attrpolstab" data-tab role="tablist">
<li class="tab-title active" role="presentational"><a href="#introtab" role="tab" tabindex="0" aria-selected="true" controls="introtab">Information</a></li>
  <li class="tab-title" role="presentational" data-reveal-ajax-tab="' . base_url('manage/attributepolicy2/getsupported/' . $idpid . '') . '"><a href="#attrpol-1" role="tab" tabindex="0" aria-selected="false" controls="attrpol-1">' . lang('rr_attributes') . '/' . lang('defaultpolicytab') . '</a></li>
  <li class="tab-title" role="presentational"data-reveal-ajax-tab="' . base_url('manage/attributepolicy2/getfedattrs/' . $idpid . '') . '"><a href="#attrpol-2" role="tab" tabindex="0"aria-selected="false" controls="attrpol-2">' . lang('fedspolicytab') . '</a></li>
  <li class="tab-title" role="presentational"><a href="#attrpol-3" role="tab" tabindex="0" aria-selected="false" controls="attrpol-3">' . lang('ecpolicytab') . '</a></li>
  <li class="tab-title" role="presentational"><a href="#attrpol-4" role="tab" tabindex="0" aria-selected="false" controls="attrpol-4">' . lang('sppolicytab') . '</a></li>
</ul>
';
?>


    <div id="attrpols" class="tabs-content">
        <section role="tabpanel" aria-hidden="false" class="content active" id="introtab">
            <pre>
            <?php

            print_r($arpsupport);
            ?>
                </pre>
        </section>
        <section role="tabpanel" aria-hidden="true" class="content" id="attrpol-1">
        </section>
        <section role="tabpanel" aria-hidden="true" class="content" id="attrpol-2">
            <h2>Second panel content goes here...</h2>
        </section>
        <section role="tabpanel" aria-hidden="true" class="content" id="attrpol-3">
            <h2>Third panel content goes here...</h2>
        </section>
        <section role="tabpanel" aria-hidden="true" class="content" id="attrpol-4">
            <h2>Fourth panel content goes here...</h2>
        </section>
    </div>



<?php
/////////MODALS
?>
    <div id="arpmdelattr" class="reveal-modal medium" data-reveal>
        <h4>Are you sure you want to remove all policies related to below attribute?</h4>

        <p>

        <div>Attribute: <span class="attributename"></span></div>
        </p>
        <div class="response"></div>
        <?php
        $hidden = array('attrid' => '', 'idpid' => '');
        echo form_open(base_url('manage/attributepolicy2/delattr/' . $idpid . ''), null, $hidden);
        $buttons = array(
            '<button type="reset" name="cancel" value="cancel" class="button alert modal-close">' . lang('rr_cancel') . '</button>',
            '<div class="yes button">' . lang('btn_deleteall') . '</div>'
        );
        echo revealBtnsRow($buttons);

        echo form_close();
        ?>

    </div>

    <div id="arpmeditglobalattr" class="reveal-modal medium" data-reveal>
        <h4>You're going to update default policy</h4>

        <p>

        <div>Attribute: <span class="attributename"></span></div>
        </p>
        <div class="response"></div>
        <?php
        $hidden = array('attrid' => '', 'idpid' => '');
        echo form_open(base_url('manage/attributepolicy2/updateattrglobal/' . $idpid . ''), null, $hidden);
        echo '<div class="row">';
        echo '<div class="small-5 medium-3 column"><label class="text-right">Support?</lable></div>';
        echo '<div class="small-7 medium-9 column">';
        echo '<div class="switch small"><input id="CheckboxSwitch" type="checkbox" name="support" value="enabled"><label for="CheckboxSwitch"></label></div>';
        echo '</div>';
        echo '</div>';
        echo '<div class="row">';
        echo '<div class="medium-3 column"><label>Policy</label></div>';
        echo '<div class="medium-9 column">' . form_dropdown('policy', array('0' => 'never', '1' => 'when required', '2' => 'when required or desired', '100' => 'unset')) . '</div>';
        echo '</div>';


        $buttons = array(
            '<button type="reset" name="cancel" value="cancel" class="button alert modal-close">' . lang('rr_cancel') . '</button>',
            '<div class="yes button">' . lang('btnupdate') . '</div>'
        );
        echo revealBtnsRow($buttons);
        echo form_close();
        ?>

    </div>
<?php
echo '<div  id="addattrsupport" class="small-12 column hidden" data-jagger-link="'.base_url('manage/attributepolicy2/getsupported/'.$idpid.'').'"><button class="small right">' . lang('btnaddattr') . '</button></div>';

///////////////////////
$nhidden = array('support' => 'enabled');

echo '<div id="arpmaddattr" class="reveal-modal medium" data-reveal>';
echo form_open(base_url('manage/attributepolicy2/updateattrglobal/' . $idpid . ''), null, $nhidden);



echo '<div class="row">';
echo '<div class="medium-3 column"><label class="text-right">Attribute</label></div>';
$attrdropdown = array();
foreach($attrdefs as $k => $v)
{
    if(!in_array($k,$arpsupport))
    {
        $attrdropdown[$k] = $v;
    }
}
echo '<div class="medium-9 column">' . form_dropdown('attrid', $attrdefs) . '</div>';
echo '</div>';
echo '<div class="row">';
echo '<div class="medium-3 column"><label class="text-right">Policy</label></div>';
echo '<div class="medium-9 column">' . form_dropdown('policy', array('0' => 'never', '1' => 'when required', '2' => 'when required or desired')) . '</div>';
echo '</div>';

$buttons = array(
    '<button type="reset" name="cancel" value="cancel" class="button alert modal-close">' . lang('rr_cancel') . '</button>',
    '<div class="yes button">' . lang('rr_add') . '</div>'
);
echo revealBtnsRow($buttons);
echo form_close();
echo '</div>';