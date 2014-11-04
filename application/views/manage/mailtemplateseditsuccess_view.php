
<div data-alert class="alert-box success ">
  <?php
  echo $success;
  ?>
  
</div>
<?php
$redirectto = base_url().'manage/mailtemplates/showlist';
?>
<script type="text/javascript">
function Redirect()
{
    window.location.href="<?php echo $redirectto;?>";
}
setTimeout('Redirect()', 1000);
</script>