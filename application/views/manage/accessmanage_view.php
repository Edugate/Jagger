<?php

echo form_open('manage/accessmanage/update/'.$resourcetype.'/'.$resourceid.'');
echo '<div id="accessmngmt" data-jagger-jsource="'.base_url('manage/accessmanage/getusersrights/'.$resourcetype.'/'.$resourceid.'').'"></div>';
echo form_close();



