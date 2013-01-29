<div id="subtitle"><h3>Identity Provider's name:&nbsp; <?php echo '<a href="'.base_url().'providers/provider_detail/idp/'.$idpid.'">'.$idpname.'</a>'; ?></h3><h4><?php echo $entityid; ?></h4></div>
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
