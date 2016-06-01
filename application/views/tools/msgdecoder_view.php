<?php

$action = base_url().'tools/addontools/msgdecoder';
echo form_open($action);
echo '<div class="small-12 column text-center"><h4>'.lang('rr_samldecoder').'</h4></div>';
echo '<div class="small-12 column">';
echo '<div class="large-3 large-text-right column"><label for="inputmsg">'.lang('rr_inputencodedmsg').'</label></div>';
echo '<div class="large-9  column code">';
echo '<textarea name="inputmsg" rows="10"></textarea>';
echo '</div>';
echo '</div>';


echo '<div class="small-12 column  ">';
echo '<div class="medium-12  column">';
echo '<div class="button-group  text-right">';
echo '<a class="small button alert cleartarget" data-jagger-textarea="inputmsg">'.lang('rr_clearbtn').'</a>';
echo '<a class="postajax button small" data-jagger-response-msg="outputmsg">'.lang('rr_submit').'</a>';
echo '</div>';
echo '</div>';
echo '</div>';


echo '<div class="small-12 column">';
echo '<div class="large-3 large-text-right column"><label for="outputmsg">'.lang('rr_result').'</label></div>';
echo '<div class="large-9 end column">';
echo '<div id="outputmsg" class="panel code xml"></div>';
echo '</div>';
echo '</div>';

echo form_close();

