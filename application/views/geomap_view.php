<?php
if(!empty($subtitle))
{
     echo $subtitle;
}
if(!empty($error))
{
    ?>
<div class="error">
    <?php
    echo htmlentities($error);
    ?>
</div>
<?php

}
if(!empty($type) && !empty($provider_id))
{
    if($type == 'idp')
    {
        $data['idpid'] = $provider_id;
        $this->load->view('/navigations/floatnav_idp_geo_view',$data);
    }
    elseif($type == 'sp')
    {
        $data['spid'] = $provider_id;
        $this->load->view('/navigations/floatnav_sp_geo_view',$data);
    }
    
}
if(!empty($mapa))
{
        echo $mapa;
}
