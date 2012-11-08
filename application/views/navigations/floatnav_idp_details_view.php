<div class="floating-menu">
<span class="accordionButton1"><b><?php echo lang('rr_gotosection');?></b></span>
<div class="accordionContent1">
<?php
$provurl = base_url().'providers/provider_detail/idp/'.$idpid;
?>
<a href="<?php echo $provurl ; ?>#basic"><?php echo lang('rr_basic');?></a>
<a href="<?php echo $provurl ; ?>#federation"><?php echo lang('rr_federations');?></a>
<a href="<?php echo $provurl ; ?>#technical"><?php echo lang('rr_technicalinformation');?></a>
<a href="<?php echo $provurl ; ?>#metadata"><?php echo lang('rr_metadata');?></a>
<a href="<?php echo $provurl ; ?>#arp">ARP</a>
<?php
echo '<a href="'.base_url().'reports/idp_matrix/show/'.$idpid.'/idp">'.lang('rr_attrsoverview').'</a>';
?>
<a href="<?php echo $provurl ; ?>#attrs"><?php echo lang('rr_supportedattributes');?></a>
<?php
echo '<a href="'.base_url().'manage/attribute_policy/globals/'.$idpid.'">'.lang('rr_attributepolicy').'</a>';
echo '<a href="'.base_url().'geolocation/show/'.$idpid.'/idp">'.lang('rr_geolocation').'</a>';
echo '<a href="'.base_url().'manage/logos/provider/idp/'.$idpid.'">'.lang('rr_logos').'</a>';
?>
</div>

</div>
