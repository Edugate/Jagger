<div id="pagetitle"><?php echo lang('rr_geolocation');?></div>
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
    }
    elseif($type == 'sp')
    {
        $data['spid'] = $provider_id;
    }
    
}
if(!empty($mapa))
{
        echo $mapa;
}
