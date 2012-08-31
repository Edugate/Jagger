<?php
echo validation_errors('<p class="error">', '</p>');

if(!empty($message))
{
        echo $message;
}

