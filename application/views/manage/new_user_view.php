
<?php
$errors= validation_errors('<div>', '</div>');

if(!empty($errors))
{
   echo '<div  data-alert class="alert-box alert ">'.$errors.'</div>';
}

if(!empty($message))
{
        echo $message;
}

