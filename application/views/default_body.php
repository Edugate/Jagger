<div id="subtitle"> 
</div>
<div class="span-20 prepend-2 last dashboard">
    <?php
    $entdelurl = base_url() . 'ajax/delbookentity/';
    $image = base_url() . 'images/icons/star--minus.png';
    ?>
    <div class="span-24 dashboardtitle">
        <h3><?php echo lang('quick_access'); ?></h3>
    </div>
    <div id="idps" class="span-12">
        <h3><?php echo lang('identityproviders'); ?></h3>
        <?php
        echo '<ul>';
        foreach ($idps as $k => $i)
        {
            echo '<li>' . $i . '<a href="' . $entdelurl . $k . '" class="bookentity"><img src="' . $image . '"/></a></li>';
        }
        echo '</ul>';
        ?>
    </div>

    <div id="sps" class="span-12 last">
        <h3><?php echo lang('serviceproviders'); ?></h3>
        <?php
        echo '<ul>';
        foreach ($sps as $k => $i)
        {
            echo '<li>' . $i . '<a href="' . $entdelurl . $k . '" class="bookentity"><img src="' . $image . '"/></a></li>';
        }
        echo '</ul>';
        ?>

    </div>
    <div id="feds" class="span-12">
        <h3><?php echo lang('federations'); ?></h3>
        <ul>
<?php
foreach ($feds as $k => $i)
{
    echo '<li>' . $i . '<a href="' . $entdelurl . $k . '" class="bookentity"><img src="' . $image . '"/></a></li>';
}
?>
        </ul>
    </div>
   <div id="feds" class="span-12 last">
       <h3><?php echo lang('rr_queue'); ?></h3>
      <div id="dashresponsecontainer"></div>
    </div>

</div>

