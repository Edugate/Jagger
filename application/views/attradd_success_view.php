<?php

if(!empty($success))
{
   echo '<div class="span-12 prepend-6"><div class="success">'.$success.'</div></div>';
}
$redirectto = base_url().'attributes/attributes/show';
?>
<script type="text/javascript">
function Redirect()
{
    window.location.href="<?php echo $redirectto;?>";
}
setTimeout('Redirect()', 1200);
</script>
