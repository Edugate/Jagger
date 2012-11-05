<?php
$this->load->helper('url');
$this->load->helper('memory');
$pageTitle = "RR :: ";
$base_url = base_url();
$current_fed_name = $this->session->userdata('current_fed_name');
$current_sp_name = $this->session->userdata('current_sp_name');
$current_idp_name = $this->session->userdata('current_idp_name');
$current_idp_menu = '';
$current_sp_menu = '';
$fed_change_link = base_url();
$sp_change_link = base_url() . "manage/settings/sp";
$idp_change_link = base_url() . "manage/settings/idp";



$pageTitle .= $this->title;
?>
<!DOCTYPE html>
<html lang="<?php echo $this->current_language; ?>">
    <head>     
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width" />
        <?php
        echo '<title>' . $pageTitle . '</title>';
        ?>
        <link rel="stylesheet" type="text/css" href="<?php echo $base_url;?>styles/jquery-ui.css"/>
        <?php
        echo '<link rel="stylesheet" type="text/css" href="' . $base_url . 'styles/screen.css" media="screen" />';
        echo '<link rel="stylesheet" type="text/css" href="' . $base_url . 'styles/jquery.jqplot.min.css" />';
        ?>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
        <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.9.1/jquery-ui.min.js"></script>
        <?php
        echo '<script type="text/javascript" src="' . $base_url . 'js/jquery.jqplot.min.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/jqplot.dateAxisRenderer.min.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/jqplot.cursor.min.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/jqplot.highlighter.min.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/jquery.tablesorter.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/jquery.inputfocus-0.9.min.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/idpAttribute.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/esapi4js/esapi-compressed.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/esapi4js/resources/i18n/ESAPI_Standard_en_US.properties.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/esapi4js/resources/Base.esapi.properties.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/locals.js"></script>';

        if (!empty($headerjs))
        {
            echo $headerjs;
        }
        if (!empty($headermap))
        {
            echo $headermap;
        }


        if (!empty($load_matrix_js))
        {
            echo '<script type="text/javascript">';
            $this->load->view('reports/matrixsp_js_view');
            echo '</script>';
        }
        ?>

    </head>
    <body class="rr3">

        <div id="oko">
            <span id="header" class="span-24">

                <span id="logo">

                    <?php
                    echo '<a href="' . $base_url . '"><img src="' . $base_url . 'images/' . $this->config->item('site_logo') . '" class="span-5"/></a>';
                    ?>
                </span>
                <?php
                if (!empty($provider_logo_url))
                {
                    echo '<img src="' . $provider_logo_url . '" class="span-3 prepend-8 provider-logo"/>';
                }
                ?>
                <div id="langicons">
                    <a href="<?php echo $base_url; ?>ajax/changelanguage/english" class="langset"><img src="<?php echo $base_url; ?>images/lang/flag-gb.png" alt="en"/></a>
                    <a href="<?php echo $base_url; ?>ajax/changelanguage/pl" class="langset"><img src="<?php echo $base_url; ?>images/lang/flag-pl.png" alt="pl"/></a>
                    <a href="<?php echo $base_url; ?>ajax/changelanguage/pt" class="langset"><img src="<?php echo $base_url; ?>images/lang/flag-pt.png" alt="pt"/></a>
                    <a href="<?php echo $base_url; ?>ajax/changelanguage/it" class="langset"><img src="<?php echo $base_url; ?>images/lang/flag-it.png" alt="it"/></a>
                </div>
                <?php
                if ($this->j_auth->logged_in())
                {
                    echo '<span class="loggedin">';
                    echo lang('urloggedas') . ' <b>' . $_SESSION['username'] . "</b>" . anchor(base_url() . "auth/logout", '<img src="' . base_url() . 'images/icons/external.png" title="Sign out"/>') . '<br /><br />';
                    ?>
                    <span id="noteheader" class="span-4" style="display:none;">
                        <span class="span-24">
                            <span class="title">Your default settings</span>
                            <?php
                            echo "<lo>";
                            if (!empty($current_fed_name))
                            {
                                echo "<li>Federation: " . $current_fed_name . " " . anchor($fed_change_link, 'change') . "</li>\n";
                            }
                            else
                            {
                                echo "<li>Federation: <b>no set</b>" . anchor($fed_change_link, 'change') . "</li>\n";
                            }

                            if (!empty($current_sp_name))
                            {
                                echo "<li>Service Provider: <b>" . $current_sp_name . "</b> " . anchor($sp_change_link, 'change') . "</li>\n";
                                $current_sp_menu = '<li><a href="' . base_url() . 'providers/provider_detail/sp">' . $current_sp_name . '</a></li>';
                            }
                            else
                            {
                                echo "<li>Service Provider: <b>not set</b> " . anchor($sp_change_link, 'change') . "</li>\n";
                            }

                            if (!empty($current_idp_name))
                            {
                                echo "<li>Identity Provider: <b>" . $current_idp_name . "</b> " . anchor($idp_change_link, 'change') . "</li>\n";
                                $current_idp_menu = '<li><a href="' . base_url() . 'providers/provider_detail/idp">' . $current_idp_name . '</a></li>';
                            }
                            else
                            {
                                echo "<li>Identity Provider: <b>not set</b> " . anchor($idp_change_link, 'change') . "</li>\n";
                            }
                            echo "</lo>"
                            ?>
                        </span>
                    </span>
                </span>

                <!-- menu -->
                <?php
                $m_register = '';
                $m_federation = '';
                $m_home = '';
                $m_idp = '';
                $m_sp = '';
                $m_general = '';
                $m_awaiting = '';
                $curr = ' class="current"';
                $currentMenu = $this->session->userdata('currentMenu');
                if (!empty($currentMenu))
                {
                    if ($currentMenu == 'register')
                    {
                        $m_register = $curr;
                    }
                    elseif ($currentMenu == 'federation')
                    {
                        $m_federation = $curr;
                    }
                    elseif ($currentMenu == 'home')
                    {
                        $m_home = $curr;
                    }
                    elseif ($currentMenu == 'awaiting')
                    {
                        $m_awaiting = $curr;
                    }
                    elseif ($currentMenu == 'idp')
                    {
                        $m_idp = $curr;
                    }
                    elseif ($currentMenu == 'sp')
                    {
                        $m_sp = $curr;
                    }
                    elseif ($currentMenu == 'general')
                    {
                        $m_general = $curr;
                    }
                    else
                    {
                        $m_home = $curr;
                    }
                }
                ?>
                <div id="divmenu">

                    <ul id="nav">
                        <li<?php echo $m_home; ?>><a href="<?php echo base_url() ?>"><?php echo lang('home'); ?></a>

                        </li>
                        <li <?php echo $m_federation; ?>><a href="<?php echo base_url(); ?>federations/manage"><?php echo lang('federations'); ?></a>
                            <ul>
                                <li><a href="<?php echo base_url(); ?>federations/federation_registration"><?php echo lang('register'); ?></a></li>
                            </ul>
                        </li>
                        <li<?php echo $m_idp ?>><a href="<?php echo base_url(); ?>providers/idp_list/show"><?php echo lang('identityproviders'); ?></a>
                            <ul><?php
            echo $current_idp_menu;
                ?>

                                <li><a href="<?php echo base_url(); ?>providers/idp_list/show"><?php echo lang('rr_list'); ?></a></li>
                                <li><a href="<?php echo base_url(); ?>providers/idp_registration"><?php echo lang('register'); ?></a></li>

                            </ul>
                        </li>
                        <li<?php echo $m_sp ?>><a href="<?php echo base_url(); ?>providers/sp_list/show"><?php echo lang('serviceproviders'); ?></a>
                            <ul><?php
                            echo $current_sp_menu;
                ?>
                                <li><a href="<?php echo base_url(); ?>providers/sp_list/show"><?php echo lang('rr_list'); ?></a></li>
                                <li><a href="<?php echo base_url(); ?>manage/attribute_requirement/sp">Attribute Requirement</a></li>
                                <li><a href="<?php echo base_url(); ?>providers/sp_registration"><?php echo lang('register'); ?></a></li>
                            </ul>
                        </li>
                        <li<?php echo $m_register; ?>><a href="<?php echo base_url(); ?>"><?php echo lang('register'); ?></a>
                            <ul><li><a href="<?php echo base_url(); ?>providers/idp_registration"><?php echo lang('identityprovider'); ?></a></li>
                                <li><a href="<?php echo base_url(); ?>providers/sp_registration"><?php echo lang('serviceprovider'); ?></a></li>
                                <li><a href="<?php echo base_url(); ?>federations/federation_registration"><?php echo lang('rr_federation'); ?></a></li>
                            </ul>
                        </li>

                        <li<?php echo $m_awaiting; ?>><a href="<?php echo base_url(); ?>reports/awaiting">
                                <?php
                                echo lang('rr_queue');
                                if (!empty($inqueue))
                                {
                                    echo "<span class=\"inqueue\">" . $inqueue . "</span>";
                                }
                                ?>
                            </a></li>
                        <li<?php echo $m_general; ?>><a href="<?php echo base_url(); ?>"><?php echo lang('general'); ?></a>
                            <ul>
                                <li><a href="<?php echo base_url(); ?>attributes/attributes/show"><?php echo lang('rr_attr_defs'); ?></a></li>
                                <li><a href="<?php echo base_url(); ?>manage/importer"><?php echo lang('rr_meta_importer'); ?></a></li>
                                <li><a href="<?php echo base_url(); ?>manage/users/showlist"><?php echo lang('rr_users'); ?> &nbsp;&gt;</a>

                                    <ul>
                                        <li><a href="<?php echo base_url(); ?>manage/users/showlist"><?php echo lang('rr_users_list'); ?></a>
                                        <li><a href="<?php echo base_url(); ?>manage/users/add"><?php echo lang('rr_newuser'); ?></a></li>
                                        <li><a href="<?php echo base_url(); ?>manage/users/remove"><?php echo lang('rr_rmuser'); ?></a></li>

                                    </ul>
                                </li>
                            </ul>
                        </li>
                        <li><a href="<?php echo base_url(); ?>"><?php echo lang('help'); ?></a></li>
                    </ul>
                </div>
                <!-- end menu -->
                <?php
            }
            ?>


        </span>

        <?php
        $height100 = '';
        if (!empty($loadGoogleMap))
        {
            $height100 = ' style="height: 100%" ';
        }
        ?>

        <div id="container"    <?php echo $height100 ?>>

            <div id="wrapper" class="span-21"  <?php echo $height100 ?> >
                <span id="content"  <?php echo $height100 ?> >
                    <?php
                    $this->load->view($content_view);
                    ?>
                </span>
            </div>
            <div id="navigation">
                <?php
                if (!empty($navigation_view))
                {
                    $this->load->view($navigation_view);
                }
                ?>
            </div>
            <div id="extra">
                <?php
                if (!empty($extra_view))
                {
                    $this->load->view($extra_view);
                }
                ?>
            </div>

        </div>
        <div id="inpre_footer"></div>
    </div>

    <div id="footer">

        <footer>
            <?php
            echo "<small>Resource Registry</small><br />";
            $disp_mem = $this->config->item('rr_display_memory_usage');
            if ($disp_mem)
            {
                echo echo_memory_usage();
            }
            ?>
        </footer>
    </div>

</body>
</html>
