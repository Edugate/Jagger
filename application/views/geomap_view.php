<?php
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

echo '<div class="small-12 columns">';

// left for the map
echo '<div class="large-8 columns">';
echo $mapdiv;
echo '</div>';


// right for form
echo '<div class="large-4 columns">';
echo $formulars;
echo '</div>';
echo '</div>';

