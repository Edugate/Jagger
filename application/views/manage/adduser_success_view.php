<?php
if(!empty($success_message))
{
echo '<div data-alert class="alert-box success">'.$success_message.'</div>';

}
$redirectto = base_url().'manage/users/showlist';
?>
<script type="text/javascript">
function Redirect()
{
    window.location.href="<?php echo $redirectto;?>";
}
setTimeout('Redirect()', 1000);
</script>
