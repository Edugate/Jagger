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
<div id="pagetitle"><?php echo lang('rr_providerdetails'); ?></div>

<div id="subtitle"><div style="float: right; display: block"><?php echo $edit_link . '&nbsp;' . $bookmark; ?></div><h3><?php echo $presubtitle . ': ' . $name; ?> <h3></div>
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

<div style="width: 100%; display: block; text-align: right"><a class="editbutton clearcache" title="<?php echo lang('clearcache'); ?>" href="<?php echo base_url().'providers/detail/refreshentity/'.$entid.''; ?>"><?php echo lang('clearcache');?></a></div>

<?php
}
?>
            <div id="providertabs">
                <ul>
<?php
foreach ($tabs as $t)
{
    echo '<li>';
    echo '<a href="#' . $t['section'] . '">' . $t['title'] . '</a>';
    echo '</li>';
}
echo '<li>';
echo '<a href="' . base_url() . 'providers/detail/showlogs/' . $entid . '">' . lang('tabLogs') . '/' . lang('tabStats') . '</a>';
echo '</li>';
?>
                </ul>
                    <?php
                    $tmpl = array('table_open' => '<table id="detailsnosort" class="zebra">');
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
                        echo '<div id="' . $t['section'] . '" class="nopadding">';
                        echo $this->table->generate();
                        $this->table->clear();
                        echo '</div>';
                    }
                    ?>
            </div>




