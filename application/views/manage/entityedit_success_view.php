<?php
$suff = $entdetail['id'];
if(!empty($success_message))
{
echo '<div data-alert class="alert-box success">'.$entdetail['entityid'].': '.$success_message.'</div>';

}
$redirectto = base_url().'providers/detail/show/'.$entdetail['id']
?>
<script type="text/javascript">
function Redirect()
{
    window.location.href="<?php echo $redirectto;?>";
}
setTimeout('Redirect()', 1000);
</script>
