<?php
$loggedin = $this->j_auth->logged_in();
$pageTitle = $this->config->item('pageTitlePref');
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
<!--[if lt IE 7]> <html lang="<?php echo $this->current_language; ?>" class="no-js ie6 oldie"> <![endif]-->
<!--[if IE 7]>    <html lang="<?php echo $this->current_language; ?>" class="no-js ie7 oldie"> <![endif]-->
<!--[if IE 8]>    <html lang="<?php echo $this->current_language; ?>" class="no-js ie8 oldie"> <![endif]-->
<!--[if gt IE 8]><!-->
<html class='no-js' lang='<?php echo $this->current_language; ?>'>
    <!--<![endif]-->
    <head>     
        <meta charset="utf-8">
        <meta content='IE=edge,chrome=1' http-equiv='X-UA-Compatible' />
        <?php
        echo '<title>' . $pageTitle . '</title>';
        ?>
        <meta content='' name='description' />
        <meta content='' name='author' />
        <meta content='width=device-width, initial-scale=1.0' name='viewport' />
        <link rel="apple-touch-icon" href="<?php echo $base_url; ?>images/touch-icon-iphone.png" />
        <link rel="apple-touch-icon" sizes="72x72" href="<?php echo $base_url; ?>images/touch-icon-ipad.png" />
        <link rel="apple-touch-icon" sizes="57x57" href="<?php echo $base_url; ?>images/apple-touch-icon-57x57-precomposed.png" />
        <link rel="apple-touch-icon" sizes="114x114" href="<?php echo $base_url; ?>images/apple-touch-icon-114x114-precomposed.png" />
        <link rel="apple-touch-icon" sizes="144x144" href="<?php echo $base_url; ?>images/apple-touch-icon-114x114-precomposed.png" />
        <link rel="stylesheet" type="text/css" href="<?php echo $base_url; ?>styles/jquery-ui.css"/>
        <link href='//fonts.googleapis.com/css?family=PT+Serif:400,700' rel='stylesheet' type='text/css'>
        <?php
        echo '<link rel="stylesheet" type="text/css" href="' . $base_url . 'styles/style.css" />';
        echo '<link rel="stylesheet" type="text/css" href="' . $base_url . 'styles/jquery.jqplot.min.css" />';
        echo '<script src="' . $base_url . 'js/modernizr-2.0.6.min.js"></script>';
        ?>

    </head>
    <body class="clearfix">
        <!--[if lt IE 7]>
                    <p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to improve your experience.</p>
                <![endif]-->
        <?php
        if (!empty($headerjs))
        {
            echo $headerjs;
        }
        if (!empty($headermap))
        {
            echo $headermap;
        }
        ?>
        <div id="toppanel">
                                    <span id="logo">
                            <?php
                            echo '<a href="' . $base_url . '"><img src="' . $base_url . 'images/' . $this->config->item('site_logo') . '" /></a>';
                            echo "\n";
                            ?>
                        </span> 
            <div style="display: inline-block">
                <?php
                if ($loggedin)
                {
                    echo '<span class="mobilehidden">' . lang('urloggedas') . '</span> <b>' . htmlentities($_SESSION['username']) . '</b>' . anchor(base_url() . "auth/logout", '<img src="' . base_url() . 'images/icons/external.png" title="Sign out"/>');
                }
                else
                {
                    echo "&nbsp";
                }
                ?>
            </div>
            <div id="langicons">
                <a href="<?php echo $base_url; ?>ajax/changelanguage/english" class="langset"><img src="<?php echo $base_url; ?>images/lang/flag-gb.png" alt="en"/></a>
                <a href="<?php echo $base_url; ?>ajax/changelanguage/pl" class="langset"><img src="<?php echo $base_url; ?>images/lang/flag-pl.png" alt="pl"/></a>
                <a href="<?php echo $base_url; ?>ajax/changelanguage/pt" class="langset"><img src="<?php echo $base_url; ?>images/lang/flag-pt.png" alt="pt"/></a>
            </div>
        </div>
        <div id="container">

            <div class="header-container">
                <header class="wrapper clearfix" role="banner">
                    <div class="header-top clearfix" style="text-align: right;">


                        <?php
                        if (!empty($provider_logo_url))
                        {
                            echo '<img src="' . $provider_logo_url . '" class="providerlogo" />';
                            echo "\n";
                        }
                        ?>


                    </div>

                    <?php
                    if ($loggedin)
                    {
                        ?>
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
                        <nav>
                            <a class="toggleMenu" href="#">Menu</a>

                            <ul class="nav">
                                <li><a href="<?php echo $base_url; ?>"><img src="<?php echo $base_url; ?>images/icons/home.png" alt="home"/></a></li>
                                <li><a href="<?php echo $base_url; ?>federations/manage"><?php echo lang('federations'); ?></a>
                                    <ul>
                                        <li><a href="<?php echo $base_url; ?>federations/manage"><?php echo lang('rr_list'); ?></a></li>
                                        <li><a href="<?php echo $base_url; ?>federations/federation_registration"><?php echo lang('register'); ?></a></li>
                                    </ul>
                                </li>
                                <li><a href="<?php echo $base_url; ?>providers/idp_list/show"><?php echo lang('identityproviders'); ?></a>
                                    <ul><?php
                    echo $current_idp_menu;
                        ?>

                                        <li><a href="<?php echo $base_url; ?>providers/idp_list/show"><?php echo lang('rr_list'); ?></a></li>
                                        <li><a href="<?php echo $base_url; ?>providers/idp_registration"><?php echo lang('register'); ?></a></li>

                                    </ul>
                                </li>
                                <li><a href="<?php echo $base_url; ?>providers/sp_list/show"><?php echo lang('serviceproviders'); ?></a>
                                    <ul><?php
                                    echo $current_sp_menu;
                        ?>
                                        <li><a href="<?php echo $base_url; ?>providers/sp_list/show"><?php echo lang('rr_list'); ?></a></li>
                                        <li><a href="<?php echo $base_url; ?>providers/sp_registration"><?php echo lang('register'); ?></a></li>
                                    </ul>
                                </li>
                                <li><a href="<?php echo $base_url; ?>"><?php echo lang('register'); ?></a>
                                    <ul><li><a href="<?php echo $base_url; ?>providers/idp_registration"><?php echo lang('identityprovider'); ?></a></li>
                                        <li><a href="<?php echo $base_url; ?>providers/sp_registration"><?php echo lang('serviceprovider'); ?></a></li>
                                        <li><a href="<?php echo $base_url; ?>federations/federation_registration"><?php echo lang('rr_federation'); ?></a></li>
                                    </ul>
                                </li>

                                <li><a href="<?php echo $base_url; ?>reports/awaiting">
                                        <?php
                                        echo lang('rr_queue');
                                        if (!empty($inqueue))
                                        {
                                            echo "<span class=\"inqueue\">" . $inqueue . "</span>";
                                        }
                                        ?>
                                    </a></li>
                                <li><a href="<?php echo $base_url; ?>"><?php echo lang('general'); ?></a>
                                    <ul>
                                        <li><a href="<?php echo $base_url; ?>manage/coc/show"><?php echo lang('coc_menulink'); ?></a></li>
                                        <li><a href="<?php echo $base_url; ?>attributes/attributes/show"><?php echo lang('rr_attr_defs'); ?></a></li>
                                        <li><a href="<?php echo $base_url; ?>manage/importer"><?php echo lang('rr_meta_importer'); ?></a></li>
                                        <li><a href="<?php echo $base_url; ?>manage/users/showlist"><?php echo lang('rr_users'); ?> &nbsp;&gt;</a>

                                            <ul>
                                                <li><a href="<?php echo $base_url; ?>manage/users/showlist"><?php echo lang('rr_users_list'); ?></a>
                                                <li><a href="<?php echo $base_url; ?>manage/users/add"><?php echo lang('rr_newuser'); ?></a></li>
                                                <li><a href="<?php echo $base_url; ?>manage/users/remove"><?php echo lang('rr_rmuser'); ?></a></li>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                                <li><a href="<?php echo $base_url; ?>"><?php echo lang('help'); ?></a></li>

                            </ul>
                        </nav>
                        <!-- end menu -->
                        <?php
                    }
                    ?>
                </header>
            </div>
            <article role="main" class="clearfix">
                <?php
                $height100 = '';
                if (!empty($loadGoogleMap))
                {
                    $height100 = ' style="height: 100%" ';
                }
                ?>

                <div   <?php echo $height100 ?>>

                    <div id="wrapper"   <?php echo $height100 ?> >
                            <?php
                            $this->load->view($content_view);
                            ?>
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
            </article>
            <div id="inpre_footer"></div>
        </div>

            <div id="footer">

                <footer>
                    <?php
                    echo '<small>'.$this->config->item('pageFooter').'</small><br />';
                    $disp_mem = $this->config->item('rr_display_memory_usage');
                    if ($disp_mem)
                    {
                        echo echo_memory_usage();
                    }
                    ?>
                </footer>
            </div>
        <div id="spinner" class="spinner" style="display:none;">
            <img id="img-spinner" src="<?php echo base_url(); ?>images/spinner1.gif" alt="Loading"/>
        </div>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
        <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.9.1/jquery-ui.min.js"></script>
        <script type="text/javascript" src="<?php echo $base_url; ?>js/jquery.uitablefilter.js"></script>
        <?php
        echo '<script type="text/javascript" src="' . $base_url . 'js/jquery.jqplot.min.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/jqplot.dateAxisRenderer.min.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/jqplot.cursor.min.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/jqplot.highlighter.min.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/jquery.tablesorter.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/jquery.inputfocus-0.9.min.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/locals.js"></script>';



        if (!empty($load_matrix_js))
        {
            echo '<script type="text/javascript">';
            $this->load->view('reports/matrixsp_js_view');
            echo '</script>';
        }
        ?>
        <!--[if lt IE 7]>
           <script src='//ajax.googleapis.com/ajax/libs/chrome-frame/1.0.3/CFInstall.min.js'></script>
           <script>
             window.attachEvent('onload',function(){CFInstall.check({mode:'overlay'})});
           </script>
           <![endif]-->
    </body>
</html>
