<?php
if (!empty($entityid))
{
    echo '<div  data-alert class="alert-box notice">'.lang('noticematrix1').'</div>';
}
?>
<div id="matrixtable" class="row">
    <div id="inmatrixtable" class="buttons small-12 columns text-center">


        <button class="editbutton button secondary small" type="button" onclick="document.getElementById('inmatrixtable').innerHTML ='<img src=\'<?php echo base_url();?>images/loading.gif\' />'; setTimeout(function(){matrixinit('<?php echo $entityid; ?>');},1000);"><?php echo lang('rrshowmatrix');?></button> 
    </div>


</div>
