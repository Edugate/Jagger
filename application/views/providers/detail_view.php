<?php
if (!empty($alerts) && is_array($alerts) && count($alerts) > 0) {
    echo '<div  class="callout warning" >';
    foreach ($alerts as $v) {
        echo '<div>' . $v . '</div>';
    }
    echo '</div>';
}

echo '<div data-jagger-getmoreajax= "' . base_url() . 'providers/detail/status/' . $entid . '" data-jagger-response-msg="prdetails" data-jagger-refreshurl="' . base_url() . 'providers/detail/status/' . $entid . '/1"></div>';

echo '<div id="prdetails" class="callout warning hidden" ></div>';
?>
<div class="off-canvas-wrapper">
    <div class="off-canvas-wrapper-inner" data-off-canvas-wrapper>


        <!-- Off Canvas Menu -->
        <?php
        echo '<aside class="off-canvas position-left" id="offCanvas" data-off-canvas>';
        echo '<ul class="vertical menu">';
        echo '<li><label>' . lang('menu_actions') . '</label></li>';
        ksort($entmenu);
        foreach ($entmenu as $v) {
            if (isset($v['label'])) {
                echo '<li><label>' . $v['label'] . '</label></li>';
            } else {
                echo '<li><a href="' . $v['link'] . '" class="' . $v['class'] . '">' . $v['name'] . '</a></li>';
            }
        }
        if (!empty($showclearcache)) {
            echo '<li><a href="' . base_url() . 'providers/detail/refreshentity/' . $entid . '" class="button clearcache small">' . lang('clearcache') . '</a></li>';
        }
        echo '</ul>';
        echo '</aside>';
        ?>

        <div id="providertabsi" class="off-canvas-content" data-off-canvas-content>




            <ul class="tabs" data-tabs id="providerdetail-tabs">
                <li class="tabks-title" style="float: left;padding: 1.25rem 1.5rem; line-height: 1;font-size: 0.75rem;"><a href="#"  data-toggle="offCanvas"><i class="fi-indent-more"></i></a></li>
                <?php
                $activetab = 'general';
                $tset = false;
                foreach ($tabs as $t) {
                    if ($tset || ($t['section'] !== $activetab)) {
                        echo '<li class="tabs-title">';
                    } else {
                        echo '<li class="tabs-title is-active">';
                        $tset = true;
                    }
                    echo '<a href="#' . $t['section'] . '">' . $t['title'] . '</a>';
                    echo '</li>';
                }
                echo '<li class="tabs-title">';
                echo '<a href="#providerlogtab">' . lang('tabLogs') . '/' . lang('tabStats') . '</a>';
                echo '</li>';
                ?>
            </ul>
            <?php
            reset($tabs);
            $tmpl = array('table_open' => '<table id="detailsnosort" class="zebra">');
            echo '<div class="tabs-content" data-tabs-content="providerdetail-tabs">';
            $tset = false;
            foreach ($tabs as $t) {
                if (isset($t['data'])) {
                    $d = $t['data'];
                    $this->table->set_template($tmpl);
                    foreach ($d as $row) {
                        if (array_key_exists('header', $row)) {
                            $cell = array('data' => $row['header'], 'class' => 'highlight', 'colspan' => 2);
                            $this->table->add_row($cell);
                        } elseif (array_key_exists('msection', $row)) {
                            $cell = array('data' => $row['msection'], 'class' => 'highlight', 'colspan' => 2);
                            $this->table->add_row($cell);
                        } elseif (array_key_exists('2cols', $row)) {
                            $cell = array('data' => $row['2cols'], 'colspan' => 2);
                            $this->table->add_row($cell);
                        } else {
                            if (isset($row['name'])) {
                                $c1 = $row['name'];
                            } else {
                                $c1 = '';
                            }
                            if (isset($row['value'])) {
                                $c2 = $row['value'];
                            } else {
                                $c2 = '';
                            }
                            $this->table->add_row($c1, $c2);
                        }
                    }
                    if ($tset || ($t['section'] !== $activetab)) {
                        echo '<div id="' . $t['section'] . '" class="tabs-panel nopadding">';
                    } else {
                        echo '<div id="' . $t['section'] . '" class="tabs-panel is-active nopadding">';
                        $tset = true;
                    }
                    echo $this->table->generate();
                    $this->table->clear();
                } elseif (!empty($t['subtab'])) {
                    echo '<div id="' . $t['section'] . '" class="tabs-panel nopadding subtab">';
                    $d = $t['subtab'];

                    echo '<ul class="tabs subtab" data-tabs id="sub-' . $t['section'] . '">';
                    $tact = true;
                    foreach ($d as $key => $dv) {
                        if ($tact && $key != 1) {
                            echo '<li class="tabs-title active">';
                            $tact = false;
                        } else {
                            echo '<li class="tabs-title">';
                        }
                        echo '<a href="#' . $dv['section'] . '">' . $dv['title'] . '</a>';
                        echo '</li>';
                    }

                    echo '</ul>';
                    $tmpl = array('table_open' => '<table id="detailsnosort" class="zebra">');
                    echo '<div class="tabs-content" data-tabs-content="sub-' . $t['section'] . '">';
                    $tact = true;
                    foreach ($d as $key => $v) {
                        if (is_array($v['data'])) {
                            foreach ($v['data'] as $row) {
                                if (array_key_exists('header', $row)) {
                                    $cell = array('data' => $row['header'], 'class' => 'highlight', 'colspan' => 2);
                                    $this->table->add_row($cell);
                                } elseif (array_key_exists('msection', $row)) {
                                    $cell = array('data' => $row['msection'], 'class' => 'section', 'colspan' => 2);
                                    $this->table->add_row($cell);
                                } elseif (array_key_exists('2cols', $row)) {
                                    $cell = array('data' => $row['2cols'], 'colspan' => 2);
                                    $this->table->add_row($cell);
                                } else {
                                    if (isset($row['name'])) {
                                        $c1 = $row['name'];
                                    } else {
                                        $c1 = '';
                                    }
                                    if (isset($row['value'])) {
                                        $c2 = $row['value'];
                                    } else {
                                        $c2 = '';
                                    }
                                    $this->table->add_row($c1, $c2);
                                }
                            }
                            if ($tact && $key != 1) {
                                echo '<div id="' . $v['section'] . '" class="tabs-panel nopadding active">';
                                $tact = false;
                            } else {
                                echo '<div id="' . $v['section'] . '" class="tabs-panel nopadding">';
                            }
                            if ($key != 1) {
                                echo $this->table->generate();
                            }
                            $this->table->clear();
                            echo '</div>';
                        } else {
                            if ($tact && $key != 1) {
                                echo '<div id="' . $v['section'] . '" class="tabs-panel nopadding active">';
                                $tact = false;
                            } else {
                                echo '<div id="' . $v['section'] . '" class="tabs-panel nopadding">';
                            }

                            echo $v['data'];

                            echo '</div>';
                        }
                    }
                    echo '</div>'; //tabs-content
                }
                echo '</div>';
            }
            // logs tab reveal //
            echo '<div id="providerlogtab" class="content" data-reveal-ajax-tab="' . base_url() . 'providers/detail/showlogs/' . $entid . '">';
            echo '</div>';
            // end logs
            echo '</div>';
            ?>
        </div>
        <a class="exit-off-canvas"></a>

    </div>
    <?php echo '</div>'; //end offcan      ?>

    <div class="metadataresult" style="display: none"></div>

    <?php
    echo '<div id="updatemembership" class="reveal-modal small" data-reveal>';
    echo '<div class="row message title">';

    echo '</div>';
    echo form_open(base_url('manage/entitystate/updatemembership'));
    echo '<input type="hidden" name="updatedata" value="" style="display:none;" />';
    $buttons = array(
        '<button type="reset" name="cancel" value="cancel" class="button alert modal-close">' . lang('rr_cancel') . '</button>',
        '<div class="yes button">' . lang('btnupdate') . '</div>'
    );
    echo revealBtnsRow($buttons);
    echo form_close();
    echo '</div>';


