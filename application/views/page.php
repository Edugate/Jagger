<?php
$loggedin = $this->j_auth->logged_in();

$isAdministrator = FALSE;
if ($loggedin)
{
    $isAdministrator = (boolean) $this->j_auth->isAdministrator();
    $args['user'] = $this->j_auth->current_user();
}
$langs = MY_Controller::guiLangs();

$pageTitle = $this->rrpreference->getTextValueByName('pageTitlePref');
$colorTheme = $this->config->item('colortheme');
if (empty($colorTheme))
{
    $colorTheme = 'default';
}
$base_url = base_url();
$pageTitle .= $this->title;

$args['langs'] = $langs;
$args['base_url'] = $base_url;

if (!empty($inqueue))
{
    $args['inqueue'] = $inqueue;
}

$args['isAdministrator'] = $isAdministrator;
$args['loggedin'] = $loggedin;

$foundation = $base_url . 'foundation/';
?>
<!DOCTYPE html>
<!--[if lt IE 7]> <html lang="<?php echo MY_Controller::getLang(); ?>" class="no-js ie6 oldie"> <![endif]-->
<!--[if IE 7]>    <html lang="<?php echo MY_Controller::getLang(); ?>" class="no-js ie7 oldie"> <![endif]-->
<!--[if IE 8]>    <html lang="<?php echo MY_Controller::getLang(); ?>" class="no-js ie8 oldie"> <![endif]-->
<!--[if IE 9]>    <html lang="<?php echo MY_Controller::getLang(); ?>" class="no-js ie9 oldie"> <![endif]-->
<!--[if gt IE 9]><!-->
<html class='no-js' lang='<?php echo MY_Controller::getLang(); ?>'>
    <!--<![endif]-->
    <head>     
        <meta charset="utf-8">
        <meta content='IE=edge,chrome=1' http-equiv='X-UA-Compatible'>
        <?php
        echo '<title>' . $pageTitle . '</title>';
        ?>
        <meta content='rr' name='description'>
        <meta content='' name='author'>
        <meta content='width=device-width, initial-scale=1.0, user-scalable=0' name='viewport'>
        <link rel="stylesheet" type="text/css" href="<?php echo $base_url; ?>styles/jquery-ui.css"/>
        <?php
        echo '<link rel="stylesheet" type="text/css" href="' . $base_url . 'styles/jquery.jqplot.min.css" />';
        echo '<link rel="stylesheet" type="text/css" href="' . $base_url . 'styles/idpselect.css" />';
        echo '<link rel="stylesheet" type="text/css" href="' . $base_url . 'styles/foundation-icons.css" />';
        echo '<link rel="stylesheet" type="text/css" href="' . $base_url . 'styles/' . $colorTheme . '.css" />';
        echo '<script src="' . $base_url . 'js/modernizr.min.js"></script>';
        ?>

    </head>
    <body>
        <!--[if lt IE 10]>
                    <div data-alert class="alert-box alert"><p>You are using an <strong>outdated</strong> browser. Please <a href="http://whatbrowser.org/">upgrade your browser</a> to improve your experience.</p></div>
                <![endif]-->
        <header>
            <?php
            $iscookieconsent = $this->rrpreference->getPreferences('cookieConsent');
            if (isset($iscookieconsent['status']) && (boolean) $iscookieconsent['status'] === TRUE && isset($iscookieconsent['value']))
            {
                $this->load->helper('cookie');
                $cookieaccepted = get_cookie('cookieAccept');
                if (empty($cookieaccepted) || $cookieaccepted != 'accepted')
                {
                    $this->load->view('cookiesconsent', $iscookieconsent);
                }
            }
            if (!empty($headerjs))
            {
                echo $headerjs;
            }
            if (!empty($headermap))
            {
                echo $headermap;
            }


            $this->load->view('toppanel', $args);
            if (!empty($titlepage) || !empty($subtitlepage))
            {

                echo '<div id="titlepage" class="fullWidth">'; // start id="titlepage"

                echo '<div class="row">'; //start titlepage part

                if (!empty($titlepage))
                {
                    if (!empty($providerlogourl))
                    {
                        echo '<div class="small-12 columns"><div class="large-8 columns text-left">' . $titlepage . '</div><div class="large-4 columns show-for-medium-up end"><img src="' . $providerlogourl . '" class="right" style="max-height: 40px; background-color: white;"/></div></div>';
                    }
                    else
                    {
                        echo '<div class="small-12 columns end text-left">' . $titlepage . '</div>';
                    }
                }
                if (!empty($subtitlepage))
                {
                    echo '<div class="small-12 columns text-center subtitle">' . $subtitlepage . '</div>';
                }

                ///////////// start submenupage
                if (!empty($submenupage))
                {
                    echo '<div><div class="small-12 columns text-right">';
                    echo '<dl class="subnav">';
                    echo '<dt></dt>';
                    foreach ($submenupage as $v)
                    {
                        if (isset($v['url']))
                        {
                            echo '<dd><a href="' . $v['url'] . '">' . $v['name'] . '</a></dd>';
                        }
                    }
                    echo '</dl>';
                    echo '</div>';
                    echo '</div>';
                }
                /////////////// end submenupage
                echo '</div>'; // end titlepage part
                echo '</div>'; // end id="titlepage"
            }
            ?>
        </header>
        <?php

        if (!empty($breadcrumbs))
        {
            echo '<div class="row fullWidth">';
            echo '<ul class="breadcrumbs">';
            foreach ($breadcrumbs as $b)
            {
                echo '<li><a href="'. $b['url'] .'" class="' . $b['class'] . '">' . $b['name'] . '</a></li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        ?>
        <div id="container" class="row">
            <div class="header-container">
                <header class="wrapper clearfix" role="banner">
                    <div class="header-top clearfix hide-for-small-only" style="text-align: right;">
                        <?php
                        if (!empty($provider_logo_url))
                        {
                            echo '<img src="' . $provider_logo_url . '" class="providerlogo" />';
                        }
                        ?>
                    </div>

                    <?php
                    if ($loggedin)
                    {
                        $showhelp = $this->session->userdata('showhelp');
                        if (!empty($showhelp) && $showhelp === TRUE)
                        {
                            echo '<a href="' . base_url() . 'ajax/showhelpstatus" id="showhelps" class="helpactive"><img src="' . base_url() . 'images/icons/info.png" class="iconhelpshow" style="display:none"><img src="' . base_url() . 'images/icons/info.png" class="iconhelpcross"></a>';
                        }
                        else
                        {
                            echo '<a href="' . base_url() . 'ajax/showhelpstatus" id="showhelps" class="helpinactive"><img src="' . base_url() . 'images/icons/info.png" class="iconhelpshow"><img src="' . base_url() . 'images/icons/info.png" class="iconhelpcross" style="display:none"></a>';
                        }
                        ?>
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

                    $height100 = ' style="min-height: 300px;height: 100%" ';
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

            <footer class="row">
                <div class="large-12 columns text-center">
                    <?php
                    $footer = $this->rrpreference->getPreferences('pageFooter');
                    if (isset($footer['status']) && (boolean) $footer['status'] === TRUE && isset($footer['value']))
                    {
                        echo '<div>' . $footer['value'] . '<div>';
                    }
                    $disp_mem = $this->rrpreference->getPreferences('rr_display_memory_usage');
                    if (isset($disp_mem['status']) && (boolean) $disp_mem['status'] === TRUE)
                    {
                        echo '<div>' . echo_memory_usage() . '</div>';
                    }
                    ?>
                </div>

            </footer>
        </div>

        <div id="spinner" class="spinner" style="display:none;">
            <img id="img-spinner" src="<?php echo $base_url; ?>images/spinner1.gif" alt="<?php echo lang('loading'); ?>"/>
        </div>
        <div style="display: none">
            <input type="hidden" name="baseurl" value="<?php echo base_url(); ?>">
            <input type="hidden" name="csrfname" value="<?php echo $this->security->get_csrf_token_name(); ?>">
            <input type="hidden" name="csrfhash" value="<?php echo $this->security->get_csrf_hash(); ?>">
        </div>


        <div id="languageset" class="reveal-modal tiny" data-reveal>
            <h4><?php echo lang('rr_langchange'); ?></h4>
            <form action="<?php echo $base_url . 'ajax/changelanguage/'; ?>" method="POST">
                <label>
                    <select  name="changelanguage">
                        <?php
                        $selset = false;
                        foreach ($langs as $key => $value)
                        {
                            if ($key === MY_Controller::getLang())
                            {
                                echo '<option value="' . $value['path'] . '" selected="selected">' . strtoupper($key) . '</option>';
                            }
                            else
                            {
                                echo '<option value="' . $value['path'] . '">(' . $key . ') ' . $value['val'] . '</option>';
                            }
                        }
                        ?>
                    </select>
                </label>
            </form>
            <a class="close-reveal-modal">&#215;</a>
        </div>



        <?php
// list js files to load 
        $jsToLoad1 = array(
            'jquery-2.1.3.min.js', 'jquery-ui-1.10.4.custom.min.js', 'jquery.uitablefilter.js', 'jquery.jqplot.min.js', 'jqplot.dateAxisRenderer.min.js',
            'jqplot.cursor.min.js', 'jqplot.highlighter.min.js', 'jquery.tablesorter.js', 'jquery.searcher.min.js',
            'foundation.min.js', 'foundation.topbar.js', 'foundation.tab.js', 'foundation.alert.js', 'foundation.reveal.js', 'jquery.simplemodal.js', 'foundation.tooltip.js');
        foreach ($jsToLoad1 as $jsFile)
        {
            echo '<script type="text/javascript" src="' . $base_url . 'js/' . $jsFile . '"></script>' . PHP_EOL;
        }
        ?>

        <?php
        if (!empty($load_matrix_js))
        {
            echo '<script type="text/javascript">';
            $this->load->view('reports/matrixsp_js_view');
            echo '</script>';
        }

        // load - need to have full url as it might be external one
        if (!empty($jsAddittionalFiles) && is_array($jsAddittionalFiles))
        {
            foreach ($jsAddittionalFiles as $jsPath)
            {
                echo '<script type="text/javascript" src="' . $jsPath . '"></script>' . PHP_EOL;
            }
        }


        if (!$loggedin)
        {
            $datalogin = array();
            if (!empty($showloginform))
            {
                $datalogin['showloginform'] = $showloginform;
                $this->load->view('loginform_view', $datalogin);
            }
            else
            {
                $this->load->view('loginform_view');
            }
            $t = 'id="duo_form"';
            echo form_open(base_url() . 'authenticate/dologin', $t);
            echo form_close();
        }
        ?>
        <script>
            $(document).foundation();
        </script>
        <?php
        // load local final js
        echo '<script type="text/javascript" src="' . $base_url . 'js/locals-v7.min.js"></script>' . PHP_EOL;

        // raw js from array
        if (!empty($rawJs) && is_array($rawJs))
        {
            echo '<script>';

            foreach ($rawJs as $v)
            {
                echo $v . PHP_EOL;
            }


            echo '</script>';
        }
        ?>

    </body>
</html>
