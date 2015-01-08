<?php


echo form_open();
echo '<div class="small-12 column">';
echo '<div class="medium-3 medium-text-right column"><label for="inputmsg">Encoded message</label></div>';
echo '<div class="medium-6 end column">';
echo '<textarea name="inputmsg" rows="10"></textarea>';
echo '</div>';
echo '</div>';


echo '<div class="small-12 column">';
echo '<div class="medium-9 medium-text-right column">';
echo '<button class="postajax medium" data-jagger-response-msg="outputmsg">submit</button>';
echo '</div>';
echo '</div>';


echo '<div class="small-12 column">';
echo '<div class="medium-3 medium-text-right column"><label for="outputmsg">Result</label></div>';
echo '<div class="medium-6 end column">';
echo '<div id="outputmsg" class="panel"></div>';
echo '</div>';
echo '</div>';

echo form_close();