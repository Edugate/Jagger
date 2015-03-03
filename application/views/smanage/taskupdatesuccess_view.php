<?php
$redirectto = base_url('smanage/taskscheduler/tasklist');
if(!empty($msg))
{


    echo '<div data-alert class="alert-box success ">'.$msg.'</div>';
}
elseif(!empty($errormsg))
{
     echo '<div data-alert class="alert-box alert ">'.$errormsg.'</div>';
}

?>
<script type="text/javascript">
function Redirect()
{
    window.location.href="<?php echo $redirectto;?>";
}
setTimeout('Redirect()', 1000);
</script>