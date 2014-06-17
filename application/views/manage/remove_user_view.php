<?php

if(!empty($message))
{
    echo $message;
}

echo validation_errors('<p  data-alert class="alert-box alert">', '</p>');


if(!empty($form))
{
   echo $form;
}

