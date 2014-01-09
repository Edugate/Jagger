<div id="pagetitle">
<?php echo lang('title_fedvalidator') ;?>
</div>
<div id="subtitle"><h3>
<?php
echo lang('rr_federation').': '.anchor($federationlink,$federationname);
?></h3>
</div>
<?php

if(!empty($successMsg))
{
   echo '<div class="success">'.$successMsg.'</div>';
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
   echo '<div class="alert">'.$errorMsg.'</div>';
}

