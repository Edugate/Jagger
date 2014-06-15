<?php
if (empty($bookmarked))
{
    $bookmark = '<a href="' . base_url() . 'ajax/bookentity/' . $entid . '" class="bookentity"><img src="' . base_url() . 'images/icons/star--plus.png" style="float:right"/></a>';
}
else
{
    $bookmark = '<a href="' . base_url() . 'ajax/delbookentity/' . $entid . '" class="bookentity"><img src="' . base_url() . 'images/icons/star--minus.png" style="float:right"/></a>';
}
?>

            <?php
            if (!empty($alerts) && is_array($alerts) and count($alerts) > 0)
            {
                echo '<div class="warnbox">';
                echo '<ol>';
                foreach ($alerts as $v)
                {
                    echo '<li>' . $v . '</li>';
                }
                echo '</ol>';
                echo '</div>';
            }
if(!empty($showclearcache)){
            ?>

<div style="width: 100%; display: block; text-align: right"><a class="button clearcache small" title="<?php echo lang('clearcache'); ?>" href="<?php echo base_url().'providers/detail/refreshentity/'.$entid.''; ?>"><?php echo lang('clearcache');?></a></div>

<?php
}
?>
<div class="off-canvas-wrap" data-offcanvas>
 <div class="inner-wrap">


    <!-- Off Canvas Menu -->
<?php
    echo '<aside class="left-off-canvas-menu">';
    echo '<ul class="off-canvas-list">';
    echo '<li><label>Actions</label></li>';
    ksort($entmenu);
    foreach($entmenu as $v)
    {
       if(isset($v['label']))
       {
          echo '<li><label>'.$v['label'].'</label></li>';
       }
       else {
         echo '<li><a href="'.$v['link'].'" class="'.$v['class'].'">'.$v['name'].'</a></li>';
       }
    }
?>
        </ul>
    </aside>







            <div id="providertabsi">
                <ul class="tabs" data-tab>
<?php
$activetab = 'general';
$tset = false;

echo '<li class="tab-title" >
        <a class="left-off-canvas-toggle" href="#"><img src="'.base_url().'images/jicons/appbar.cog.png" style="height: 20px"/></a>
      </li>';


//<a class="left-off-canvas-toggle menu-icon" href="#" ><span></span></a></li>';
foreach ($tabs as $t)
{
    if($tset || ($t['section'] !== $activetab))
    { 
       echo '<li class="tab-title">';
    }
    else
    {
       echo '<li class="tab-title active">';
       $tset = true;

    }
    echo '<a href="#' . $t['section'] . '">' . $t['title'] . '</a>';
    echo '</li>';
}
echo '<li class="tab-title">';
echo '<a href="#providerlogtab" data-reveal-ajax-tab="' . base_url() .'providers/detail/showlogs/' . $entid . '">' . lang('tabLogs') . '/' . lang('tabStats') . '</a>';
echo '</li>';
?>
                </ul>
                    <?php
                    $tmpl = array('table_open' => '<table id="detailsnosort" class="zebra">');
                    echo '<div class="tabs-content">';
                    $tset = false;
                    foreach ($tabs as $t)
                    {
                        $d = $t['data'];
                        $this->table->set_template($tmpl);
                        foreach ($d as $row)
                        {
                            if (array_key_exists('header', $row))
                            {
                                $cell = array('data' => $row['header'], 'class' => 'highlight', 'colspan' => 2);
                                $this->table->add_row($cell);
                            }
                            elseif (array_key_exists('2cols', $row))
                            {
                                $cell = array('data' => $row['2cols'], 'colspan' => 2);
                                $this->table->add_row($cell);
                            }
                            else
                            {
                                if (isset($row['name']))
                                {
                                    $c1 = &$row['name'];
                                }
                                else
                                {
                                    $c1 = '';
                                }
                                if (isset($row['value']))
                                {
                                    $c2 = &$row['value'];
                                }
                                else
                                {
                                    $c2 = '';
                                }
                                $this->table->add_row($c1, $c2);
                            }
                        }
                        if($tset || ($t['section'] !== $activetab))
                        {
                            echo '<div id="' . $t['section'] . '" class="content nopadding">';
                        }
                        else
                        {
                            echo '<div id="' . $t['section'] . '" class="content active nopadding">';
                            $tset = true;
                            
                        }
                        echo $this->table->generate();
                        $this->table->clear();
                        echo '</div>';
                    }
                    // logs tab reveal //
                    echo '<div id="providerlogtab" class="content">';
                    echo '</div>';
                    // end logs
                    echo '</div>';
                    ?>
            </div>
 <a class="exit-off-canvas"></a>

  </div>
<?php echo '</div>'; //end offcan ?>

<div class="metadataresult" style="display: none"></div>

