<div id="pagetitle"><?php echo lang('fedejoinform');?></div>


<?php
if(!empty($subtitle))
{
   echo '<div id="subtitle"><h3><a href="'.base_url().'providers/detail/show/'.$providerid.'">'.$name.'</a></h3><h4>'.$entityid.'</h4></div>';
}
if(!empty($success_message))
{
    echo '<div class="success">'.$success_message.'</div>';
}
if(!empty($error_message))
{
    echo '<div class="alert">'.$error_message.'</div>';

}
if(!empty($form))
{
   echo $form;

}
