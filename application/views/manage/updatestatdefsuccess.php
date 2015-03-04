<?php

if(!empty($message))
{
 echo '<div data-alert class="alert-box success">'.$message.'</div>';

}
$redirectto = base_url('manage/statdefs/show/').'/'.$providerid;
?>
<script type="text/javascript">
function Redirect()
{
    window.location.href="<?php echo $redirectto;?>";
}
setTimeout('Redirect()', 1000);
</script>