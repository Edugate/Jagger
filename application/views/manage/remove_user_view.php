<?php

if(!empty($message))
{
    echo $message;
}

echo validation_errors('<p class="error">', '</p>');


if(!empty($form))
{
   echo $form;
}

