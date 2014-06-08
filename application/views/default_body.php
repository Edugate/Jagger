<div class="row">
    <?php
    $entdelurl = base_url() . 'ajax/delbookentity/';
    $feddelurl = base_url() . 'ajax/delbookfed/';
    $image = base_url() . 'images/icons/star--minus.png';
    ?>
 
    <div id="idps" class="box span-11 large-6 medium-5 small-6 columns">
        <div class="box-header"><h4><?php echo '<a href="'.base_url().'providers/idp_list/show">'.lang('identityproviders').'</a>'; ?></h4></div>
        <div class="box-content">
        <?php
        echo '<ul>';
        foreach ($idps as $k => $i)
        {
            echo '<li>' . $i . '<a href="' . $entdelurl . $k . '" class="delbookentity"><img src="' . $image . '"/></a></li>';
        }
        echo '</ul>';
        ?>
        </div>
    </div>

    <div id="sps" class="box span-11 last large-6 medium-5 small-6 columns">
        <div class="box-header"><h4><?php echo '<a href="'.base_url().'providers/sp_list/show">'.lang('serviceproviders').'</a>'; ?></h4></div>
        <div class="box-content">
        <?php
        echo '<ul>';
        foreach ($sps as $k => $i)
        {
            echo '<li>' . $i . '<a href="' . $entdelurl . $k . '" class="delbookentity"><img src="' . $image . '"/></a></li>';
        }
        echo '</ul>';
        ?>
        </div>

    </div>
    <div id="feds" class="box span-11 large-6 columns">
        <div class="box-header"><h4><?php echo '<a href="'.base_url().'federations/manage">'.lang('federations').'</a>'; ?></h4></div>
        <div class="box-content">
        <ul>
<?php
foreach ($feds as $k => $i)
{
    echo '<li>' . $i . '<a href="' . $feddelurl . $k . '" class="delbookfed"><img src="' . $image . '"/></a></li>';
}
?>
        </ul>
        </div>
    </div>

   <div id="queue" class="box span-11 large-6 columns">
       <div class="box-header"><h4><?php echo lang('rr_queue'); ?></h4></div>
      <div class="box-content"><div id="dashresponsecontainer"></div></div>
    </div>

</div>

