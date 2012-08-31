<?php

if (!empty($javascript))
{
    echo $javascript;
}

if (!empty($error_message))
{
    echo "<span class=\"alert\">$error_message</span>";
}

if (!empty($form))
{

    echo $form;
}
