<?php

if(!empty($success))
{
   echo '<div class="span-12 prepend-6"><div class="success">'.$success.'</div></div>';
}
else
{
   log_message('error', 'missing argument from controller');

}
