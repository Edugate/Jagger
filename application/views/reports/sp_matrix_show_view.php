<?php
if(!empty($entityid))
{
echo '<div id="subtitle">';
$imgsrc = '<img src="'.base_url().'images/icons/block-share.png" />';
echo $arpcachetimeicon.' Attribute overview for entityID: '. $entityid . ' '.anchor(''.base_url().'providers/provider_detail/sp/'.$spid,$imgsrc) ;
echo '</div>';

}
?>
<div id="matrixtable" style="text-align: center; width: 100%; overflow: auto;" class="span-24">
<br />
<br />
<br />
<br />
<div id="button" class="prepend-10">


<button type="button" onclick="document.getElementById('matrixtable').innerHTML ='<br /><br /><br /><img src=\'<?php echo base_url();?>images/loading.gif\' />'; setTimeout(function(){matrixinit('<?php echo $entityid; ?>');},1000);">Show matrix</button> 
</div>


</div>
