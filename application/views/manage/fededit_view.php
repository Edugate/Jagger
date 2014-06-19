<?php

if (!empty($javascript))
{
    echo $javascript;
}
if (!empty($error_message))
{
    echo '<div alertdata-alert class="alert-box alert">'.$error_message.'</div>';
}
if(!empty($success_message))
{
    echo '<div alertdata-alert class="alert-box success">'.$success_message.'</div>';
}

if (!empty($form))
{
    echo $form;
}

