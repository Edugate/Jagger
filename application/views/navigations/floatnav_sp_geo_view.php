<div class="floating-menu">
<span class="accordionButton1"><b>go to section</b></span>
<div class="accordionContent1">
<?php
$provurl = base_url().'providers/provider_detail/sp/'.$spid;
?>
<a href="<?php echo $provurl ; ?>#basic">Basic</a>
<a href="<?php echo $provurl ; ?>#federation">Federations</a>
<a href="<?php echo $provurl ; ?>#technical">Technical Information</a>
<a href="<?php echo $provurl ; ?>#metadata">Metadata</a>
<a href="<?php echo $provurl ; ?>#reqattrs">Required Attrs</a>
<?php
echo '<a href="'.base_url().'geolocation/show/'.$spid.'/sp">Geolocation</a>';
echo '<a href="'.base_url().'manage/logos/provider/sp/'.$spid.'">Logos</a>';
?>
</div>

</div>
