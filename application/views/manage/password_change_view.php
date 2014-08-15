<?php
    $errors_v = validation_errors('<span class="span-12">', '</span><br />');
    if (!empty($errors_v)) {
        echo '<div data-alert class="small-12 medium-10 large-8 small-centered columns alert-box alert">';
        echo $errors_v;
        echo '</div>';
    }
if(!empty($message))
{
   echo '<div data-alert class="small-12 medium-10 large-8 small-centered columns alert-box success">'.$message.'</div>';
}
if(!empty($form))
{
   echo $form;
}
