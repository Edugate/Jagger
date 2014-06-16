<?php

if(!empty($successMsg))
{
   echo '<div data-alert class="alert-box success">'.$successMsg.'</div>';
?>
<script type="text/javascript">
function Redirect()
{
    window.location.href="<?php echo $federationlink;?>";
}
setTimeout('Redirect()', 1000);
</script>
<?php
}
elseif(!empty($errorMsg))
{
   echo '<div data-alert class="alert-box alert">'.$errorMsg.'</div>';
}

