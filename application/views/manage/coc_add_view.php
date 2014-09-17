<?php
$errors_v = validation_errors('<div>', '</div>');
if (!empty($errors_v)) {
    echo '<div data-alert class="alert-box alert">'.$errors_v.'</div>';
}
if(!empty($success_message))
{
   echo '<div data-alert class="alert-box success">'.$success_message.'</div>';

}
if(!empty($form))
{
       echo $form;

}
