
<div class="alert"><?php echo lang('error_usernotexist');?></div>
<div class="result"></div>


<?php
$attributes = array('id' => 'applyforaccount');
echo form_open(''.base_url().'auth/fedregister',$attributes);

echo '<button type="submit" name="applyforaccount"  value="'.base_url().'auth/fedregister">'.lang('applyforaccount').'</button>';

echo form_close();
