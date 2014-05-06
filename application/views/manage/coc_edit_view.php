<div id="pagetitle"><?php echo lang('title_entcatedit');?></div>
<div id="subtitle"><h3><?php echo htmlentities($coc_name) ?></h3></div>
<?php
$errors_v = validation_errors('<p class="error">', '</p>');
if (!empty($errors_v)) {
    echo '<div class="alert">'.$errors_v.'</div>';
}
if(!empty($success_message))
{
   echo '<div class="success">'.$success_message.'</div>';

}
if(!empty($form))
{
       echo $form;

}
