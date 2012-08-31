<?php

if(!empty($error_message))
{
   echo '<div class="span-12 prepend-6"><div class="alert">'.$error_message.'</div>';

}

if(!empty($result))
{
   echo $result;

}

if(!empty($success_message))
{
  echo "<div class=\"success\">".$success_message."</div>";
}
