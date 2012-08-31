<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
if(!empty($message))
{
	echo "<span>".$message."</span>";
}
if(!empty($error_message))
{
	echo "<span class=\"alert\">".$error_message."</span>";
}
?>

<div id="responsecontainer"></div>
