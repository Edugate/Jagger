
<div id="pagetitle"><?php echo lang('newstatdefform');?></div>
<div id="subtitle"><h3><?php echo anchor(base_url().'providers/detail/show/'.$providerid, $providername ) ;?></h3><h4><?php echo $providerentity;?></h4></div>
<?php

if(!empty($message))
{
 echo '<div class="success">'.$message.'</div>';

}
