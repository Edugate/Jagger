<?php
   echo "<h2>Resource Registry Setup Page</h2>";
echo validation_errors('<p class="error">', '</p>');

if(!empty($f))
{
   echo $f;
}
if(!empty($message))
{
   echo $message;
}
