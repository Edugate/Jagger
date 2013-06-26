<div id="subtitle"><h3><a href="<?php echo base_url().'providers/detail/show/'.$entdetail['id'];?>"><?php echo $entdetail['displayname'] ; ?></a></h3><h4><?php echo $entdetail['entityid']; ?></h4> </div>
<?php
$suff = $entdetail['id'];
if(!empty($success_message))
{
echo '<div class="success">'.$success_message.'</div>';
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
