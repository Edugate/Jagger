<div id="pagetitle"><?php echo lang('rr_provideingattrsoverview');?></div>
<?php
if (!empty($entityid))
{

    echo '<div id="subtitle"><h3>';
    $imgsrc = '<img src="' . base_url() . 'images/icons/block-share.png" />';
    echo $arpcachetimeicon . ' Service Provider: '. anchor('' . base_url() . 'providers/provider_detail/sp/' . $spid, $entityname);
    echo '</h3><h4>'.$entityid.'<h4></div>';

    echo '<div id="noticeblock">'.lang('noticematrix1').'</div>';
}
?>
<div id="matrixtable">
    <br />
    <br />
    <br />
    <br />
    <div class="buttons">


        <button class="btn" type="button" onclick="document.getElementById('matrixtable').innerHTML ='<br /><br /><br /><img src=\'<?php echo base_url();?>images/loading.gif\' />'; setTimeout(function(){matrixinit('<?php echo $entityid; ?>');},1000);">Show matrix</button> 
    </div>


</div>
