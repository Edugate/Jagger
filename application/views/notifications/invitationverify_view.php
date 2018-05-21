<?php


echo '<div class="hidden alert-box alert" id="invverifyerror"></div>';

echo form_open(base_url('notifications/invitations/invverification'), array('id' => 'invverify')) . '<div class="row">' .

    '<div class="small-12 column">
<div class="column small-12 medium-3 text-right">
<label for="token">Token</label></div><div class="small-12 column medium-9"><input name="token"></div></div>' .
    '<div class="small-12 column">
<div class="column small-12 medium-3 text-right"><label for="token">Verification Key</label></div><div class="small-12 column medium-9"><input name="verifykey"></div></div>' .

    '</div>
<div class="column small-12  text-center"><div class="buttons"><button type="submit" name="verifyinvitation"  class="button" value="verify">verify</button></div></div>';

form_close();