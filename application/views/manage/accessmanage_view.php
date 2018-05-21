<?php

echo form_open('manage/accessmanage/update/'.$resourcetype.'/'.$resourceid.'');
echo '<div id="accessmngmt" data-jagger-jsource="'.base_url('manage/accessmanage/getusersrights/'.$resourcetype.'/'.$resourceid.'').'"></div>';
echo form_close();
echo '<div>';
echo '<div class="small-12 column text-center">
<div class="alert-box">Invitation</div>
<div data-alert id="invusrresp" class="alert-box alert hidden"></div>
</div>';
echo form_open(base_url('notifications/invitations/inviteuser/entity/'.$resourceid.''), array('id'=>'inviteuserform'));
echo '<div class="column small-12">
<div class="column small-12 medium-3 text-right"><label for="email">Email</label></div>
<div class="column small-12 medium-3 end"><input name="email" type="email"></div>
</div>';

echo '<div class="column small-12">
<div class="column small-12 medium-3 text-right"><label for="email_confirm">Email confirm</label></div>
<div class="column small-12 medium-3 end"><input type="email" name="email_confirm"></div>
</div>';
echo '<div class="column small-12 text-right"><div class="column small-12 medium-3"><label for="permissions">Access level</label></div>
<div class="column small-12 medium-3 end">'.form_dropdown('permissions',array('0'=>'Select','plusrw'=>'Edit resource','plusmanage'=>'Manage resource')).'</div></div>';
echo '<div class="column small-12"><div class="column small-12 medium-3">&nbsp;</div><div class="column small-12 medium-3 end"><button type="submit" class="button">Send</button></div></div>';
echo form_close();
echo '</div>';



