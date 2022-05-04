<?php
$defaultMsg = "If you require an account to manage your entities please contact our support team.";
$noticeMsg = $this->config->item('req_fedaccount_notice') ?: $defaultMsg;
?>
<h3><?php echo lang('rr_feduser_register_title');?></h3>
<div class="alert-box alert"><?php echo lang('error_usernotexist');?></div>
<br />
<br />
<div class="result notice"><?php echo html_escape($noticeMsg);  ?></div>
<br />



