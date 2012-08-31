<?php
if(!empty($subtitle))
{
   echo '<div id="subtitle">'.$subtitle.'</div>';
}
if(!empty($success_message))
{
    echo '<div class="span-12 prepend-6"><div class="success">'.$success_message.'</div></div>';
}
if(!empty($error_message))
{
    echo '<div class="span-12 prepend-6"><div class="alert">'.$error_message.'</div></div>';

}
if(!empty($form))
{
   echo $form;

}
