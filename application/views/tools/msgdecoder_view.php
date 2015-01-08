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
echo '<ul class="button-group  right">';
echo '<li><button class="small alert cleartarget" data-jagger-textarea="inputmsg">'.lang('rr_clearbtn').'</button></li>';
echo '<li><button class="postajax small" data-jagger-response-msg="outputmsg">'.lang('rr_submit').'</button></li>';
echo '</ul>';
echo '</div>';
echo '</div>';


echo '<div class="small-12 column">';
echo '<div class="large-3 large-text-right column"><label for="outputmsg">'.lang('rr_result').'</label></div>';
echo '<div class="large-9 end column">';
echo '<div id="outputmsg" class="panel code xml"></div>';
echo '</div>';
echo '</div>';

echo form_close();