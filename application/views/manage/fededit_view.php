<?php

if (!empty($javascript))
{
    echo $javascript;
}
if(!empty($subtitle))
{
    echo "<div id=\"subtitle\">".$subtitle."</div>";
}
if (!empty($error_message))
{
    echo "<span class=\"alert\">$error_message</span>";
}

if (!empty($form))
{
    echo $form;
}
if(!empty($success_message))
{
    echo "<div class=\"success\">".$success_message."</div>";
}

