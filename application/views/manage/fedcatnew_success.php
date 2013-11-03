<?php

if(!empty($success_message))
{
 echo '<div class="success">'.$success_message.'</div>';
}
$redirectto = base_url().'manage/fedcategory/show';
echo anchor($redirectto,lang('rr_go'));
?>
<script type="text/javascript">
function Redirect()
{
    window.location.href="<?php echo $redirectto;?>";
}
setTimeout('Redirect()', 1000);
</script>

