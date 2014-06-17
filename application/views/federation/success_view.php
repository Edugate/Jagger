<?php

if(!empty($success))
{
   echo '<div  data-alert class="alert-box success">'.$success.'</div>';
}
else
{
   log_message('error', 'missing argument from controller');

}
