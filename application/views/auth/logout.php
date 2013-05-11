<?php 
$redirect_to = base_url();
header("Refresh: 1;url=".$redirect_to."");
$shibcnf = $this->config->item('Shibboleth');
?>
<html>
<head>
</head>
<body>
<?php
   if(isset($shibcnf['enabled']) && $shibcnf['enabled'] === TRUE && isset($shibcnf['logout_uri']))
   {
?>
<iframe style="visibility:hidden" frameborder=0 marginheight=0 marginwidth=0 scrolling=no src="<?php echo $shibcnf['logout_uri'];?>"></iframe>
<?php
   }
?>
</body>
<html>
<?php
