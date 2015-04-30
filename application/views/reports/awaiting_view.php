<?php

if(!empty($message))
{
	echo '<div data-alert class="alert-box info">'.$message.'</span>';
}
if(!empty($error_message))
{
	echo '<div data-alert class="alert-box error">'.$error_message.'</div>';
}


echo '<div id="responsecontainer"></div>';
