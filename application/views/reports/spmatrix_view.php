<?php
if (!empty($spid))
{
    echo '<div  data-alert class="alert-box notice">'.lang('noticematrix1').'</div>';
}
echo '<div class="small-12 column text-center"><button id="spmatrixload" data-jagger-ajaxurl="'.base_url('reports/spmatrix/getdiag/'.$spid.'').'" data-jagger-spid="'.$spid.'" class="secondary small">'.lang('rrshowmatrix').'</button></div>';
echo '<div id="spmatrixdiv" class="small-12 column"></div>';

