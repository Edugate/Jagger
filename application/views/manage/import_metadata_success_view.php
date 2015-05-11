<?php
if(!empty($success_message))
{
   echo '<div data-alert class="alert-box success">'.$success_message.'</div>';
}
if(!empty($success_details))
{
    foreach($success_details as $v)
    {
        echo '<div>'.$v.'</div>';
    }
   
}

