<div class="row">
    <?php
    $entdelurl = base_url() . 'ajax/bookmarkentity/';
    $feddelurl = base_url() . 'ajax/bookfed/';
    if(!empty($alertdashboard) and is_array($alertdashboard))
    {
        $alertdashboardHTML = implode('<br />',$alertdashboard);
        echo '<div data-alert class="alert-box error">'.html_escape($alertdashboardHTML).'</div>';
    }
    ?>
 
    <div class="small-12 columns" data-equalizer>
    <div id="idps" class="box small-12 medium-6 columns" data-equalizer-watch>
        <div class="box-header"><h4><?php echo '<a href="'.base_url().'providers/idp_list/showlist"><i class="fi-list"></i> '.lang('identityproviders').'</a>'; ?></h4></div>
        <div class="box-content">
        <?php
        echo '<ul class="no-bullet">';
        foreach ($idps as $k => $i)
        {
            echo '<li data-jagger-onsuccess="hide">' . $i . '  <a href="' . $entdelurl . $k . '" class="updatebookmark" data-jagger-bookmark="del"><i class="fi-x-circle inline right"></i></a></li>';
        }
        echo '</ul>';
        ?>
        </div>
    </div>

    <div id="sps" class="box large-6  small-12 medium-6 columns end" data-equalizer-watch>
        <div class="box-header"><h4><?php echo '<a href="'.base_url().'providers/sp_list/showlist"><i class="fi-list"></i> '.lang('serviceproviders').'</a>'; ?></h4></div>
        <div class="box-content">
        <?php
        echo '<ul class="no-bullet">';
        foreach ($sps as $k => $i)
        {
            echo '<li data-jagger-onsuccess="hide">' . $i . '<a href="' . $entdelurl . $k . '" class="updatebookmark" data-jagger-bookmark="del"><i class="fi-x-circle iniline right"></i></a></li>';
        }
        echo '</ul>';
        ?>
        </div>

    </div>
    </div>
    <div class="small-12 columns" data-equalizer>

    <div id="feds" class="box small-12 medium-6 columns" data-equalizer-watch>
        <div class="box-header"><h4><?php echo '<a href="'.base_url().'federations/manage"><i class="fi-list"></i> '.lang('federations').'</a>'; ?></h4></div>
        <div class="box-content">
        <ul class="no-bullet">
<?php
foreach ($feds as $k => $i)
{
    echo '<li data-jagger-onsuccess="hide">' . $i . '<a href="' . $feddelurl . $k . '" class="updatebookmark" data-jagger-bookmark="del"><i class="fi-x-circle inline right"></i></a></li>';
}
?>
        </ul>
        </div>
    </div>

   <div id="queue" class="box small-12 medium-6 columns">
       <div class="box-header"><h4><a href="<?php echo base_url().'reports/awaiting'?>"><i class="fi-list"></i> <?php echo lang('rr_queue'); ?></a></h4></div>
      <div class="box-content"><div id="dashresponsecontainer"></div></div>
    </div>
   </div>

</div>


