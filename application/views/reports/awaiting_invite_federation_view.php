<?php

if (!empty($error_message))
{
    echo '<div data-alert class="alert-box alert">' . $error_message . '</div>';
}

if (!empty($result))
{
    echo $result;
}

if (!empty($success_message))
{
    echo '<div data-alert class="alert-box  success">' . $success_message . '</div>';
}
