<?php
$loggedin = $this->jauth->isLoggedIn();
$isReadOnly = null;
if (empty($sideicons)) {
    $sideicons = array();
}
$isAdministrator = false;
if ($loggedin) {
    $isAdministrator = (boolean)$this->jauth->isAdministrator();
    $args['user'] = $this->jauth->getLoggedinUsername();
    $isReadOnly = $this->session->userdata('readonly');
}
$langs = MY_Controller::guiLangs();

$pageTitle = $this->rrpreference->getTextValueByName('pageTitlePref');
$colorTheme = $this->config->item('colortheme');
if (empty($colorTheme)) {
    $colorTheme = 'default';
}
$base_url = base_url();
$pageTitle .= $this->title;

$args['langs'] = $langs;
$args['base_url'] = $base_url;

if (!empty($inqueue)) {
    $args['inqueue'] = $inqueue;
}

$args['isAdministrator'] = $isAdministrator;
$args['loggedin'] = $loggedin;
$shibCnf = $this->config->item('Shibboleth');
$shibLogoutUri = null;
if (isset($shibCnf['logout_uri'])) {
    $shibLogoutUri = $shibCnf['logout_uri'];
}
$args['shibLogoutUri'] = $shibLogoutUri;
$foundation = $base_url . 'foundation/';
?>
<!DOCTYPE html>
<!--[if lt IE 7]>
<html lang="<?php echo MY_Controller::getLang(); ?>" class="no-js ie6 oldie"> <![endif]-->
<!--[if IE 7]>
<html lang="<?php echo MY_Controller::getLang(); ?>" class="no-js ie7 oldie"> <![endif]-->
<!--[if IE 8]>
<html lang="<?php echo MY_Controller::getLang(); ?>" class="no-js ie8 oldie"> <![endif]-->
<!--[if IE 9]>
<html lang="<?php echo MY_Controller::getLang(); ?>" class="no-js ie9 oldie"> <![endif]-->
<!--[if gt IE 9]><!-->
<html class='no-js' lang='<?php echo MY_Controller::getLang(); ?>'>
<!--<![endif]-->
<head>
    <meta charset="utf-8">
    <meta content='IE=edge,chrome=1' http-equiv='X-UA-Compatible'>
    <?php
    echo '<title>' . $pageTitle . '</title>' . PHP_EOL .
        '<meta content=\'rr\' name=\'description\'>' . PHP_EOL .
        '<meta content=\'\' name=\'author\'>' . PHP_EOL .
        '<meta content=\'width=device-width, initial-scale=1.0, user-scalable=0\' name=\'viewport\'>' . PHP_EOL .
        '<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css" integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">'.
        '<link rel="stylesheet" type="text/css" href="' . $base_url . 'styles/' . $colorTheme . '.css" />' . PHP_EOL;
    ?>
    <style>

    </style>
</head>
<body class="no-js">
<noscript>
    <div data-alert class="alert-box alert"><h5>JavaScript is not enabled in you browser!</h5></div>
</noscript>
<!--[if lt IE 10]>
<div data-alert class="alert-box alert"><p>You are using an <strong>outdated</strong> browser. Please <a
        href="http://whatbrowser.org/">upgrade your browser</a> to improve your experience.</p></div>
<![endif]-->

<?php
$iscookieconsent = $this->rrpreference->getPreferences('cookieConsent');
$breadcrumbsConf = $this->rrpreference->getPreferences('breadcrumbs');
$breadcrumbsEnabled = false;
if (!empty($breadcrumbsConf) && !empty($breadcrumbsConf['status'])) {
    $breadcrumbsEnabled = true;
}

$showPagetitles = $this->rrpreference->getPreferences('titleheader');
if (isset($iscookieconsent['status']) && (boolean)$iscookieconsent['status'] === true && isset($iscookieconsent['value'])) {
    $this->load->helper('cookie');
    $cookieaccepted = get_cookie('cookieAccept');
    if (empty($cookieaccepted) || $cookieaccepted !== 'accepted') {
        $this->load->view('cookiesconsent', $iscookieconsent);
    }
}
$args['breadcrumbsEnabled'] = $breadcrumbsEnabled;

echo '<header>';
$this->load->view('f6/toppanel', $args);
echo '</header>';

if (!empty($showPagetitles) && !empty($showPagetitles['status']) && (!empty($titlepage) || !empty($subtitlepage))) {

    echo '<div id="titlepage" class="row expanded">'; // start id="titlepage"

    echo '<div class="row">'; //start titlepage part

    if (!empty($titlepage)) {
        if (!empty($providerlogourl)) {
            echo '<div class="large-8 columns text-left">' . $titlepage . '</div><div class="large-4 columns text-right show-for-medium-up"><img src="' . $providerlogourl . '" class="right" style="max-height: 40px; background-color: white;"/></div>';
        } else {
            echo '<div>' . $titlepage . '</div>';
        }
    }
    if (!empty($subtitlepage)) {
        echo '<div class="small-12 columns text-center subtitle">' . $subtitlepage . '</div>';
    }

    ///////////// start submenupage
    if (!empty($submenupage)) {
        echo '<div><div class="small-12 columns text-right">';
        echo '<dl class="subnav">';
        echo '<dt></dt>';
        foreach ($submenupage as $v) {
            if (isset($v['url'])) {
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

<?php

if ($breadcrumbsEnabled === true) {
    if ($loggedin) {
        $prefBreadcrumbs = array(array('url' => base_url(), 'name' => lang('dashboard')));
    } else {
        $prefBreadcrumbs = array();
    }
    echo '<div class="row  expanded"><div class="small-12 column"><nav aria-label="You are here:" role="navigation">';
    echo '<ul class="breadcrumbs">';


    if (empty($breadcrumbs)) {
        $breadcrumbs = array();
    }
    $groupsBreadcrumbs = array($prefBreadcrumbs, $breadcrumbs);
    foreach ($groupsBreadcrumbs as $barray) {

        foreach ($barray as $b) {
            $rawAttrs = '';
            $aClass = '';
            if (isset($b['class'])) {
                $aClass = $b['class'];
            }
            if (isset($b['type'])) {
                if ($b['type'] === 'current') {
                    $rawAttrs = 'class="disabled"';
                } elseif ($b['type'] === 'unavailable') {
                    $rawAttrs = 'class="disabled" ';
                }
                echo '<li ' . $rawAttrs . '>' . $b['name'] . '</li>';
            } else {
                echo '<li ' . $rawAttrs . '><a href="' . $b['url'] . '" class="' . $aClass . '">' . $b['name'] . '</a></li>';
            }
        }
    }
    echo '</ul></nav>';
    echo '</div>';
    echo '</div>';


}
?>
<div id="container" class="row">
    <div class="header-container">
        <header class="wrapper clearfix" role="banner">
            <div class="header-top clearfix hide-for-small-only" style="text-align: right;">
                <?php
                if (!empty($provider_logo_url)) {
                    echo '<img src="' . $provider_logo_url . '" class="providerlogo" />';
                }
                ?>
            </div>

            <?php
            if ($loggedin) {
                $showhelp = $this->session->userdata('showhelp');
                if ($showhelp === true) {
                    $sideicons[] = '<a href="' . base_url() . 'ajax/showhelpstatus" id="showhelps" class="helpactive alert active"><i class="fa fa-info"></i></a>';
                } else {
                    $sideicons[] = '<a href="' . base_url() . 'ajax/showhelpstatus" id="showhelps" class="helpinactive"><i class="fa fa-info"></i></a>';
                }
            }
            ?>
        </header>
    </div>
    <article role="main" class="clearfix">


        <div id="wrapper" class="row column expanded">
            <?php
            if ($isReadOnly === true) {
                echo '<div data-alert class="alert-box alert">System is in ReadOnly mode</div>';
                $this->session->set_userdata('readonly', null);

            }
            $this->load->view($content_view);
            ?>

        </div>
        <div id="navigation">
            <?php
            if (!empty($navigation_view)) {
                $this->load->view($navigation_view);
            }
            ?>
        </div>
        <div id="extra">
            <?php
            if (!empty($extra_view)) {
                $this->load->view($extra_view);
            }
            ?>
        </div>


    </article>
    <div id="inpre_footer"></div>
</div>

<div id="footer" class="row expanded">

    <footer class="row">
        <div class="large-12 columns text-center">
            <?php
            $footer = $this->rrpreference->getPreferences('pageFooter');
            if (isset($footer['status']) && (boolean)$footer['status'] === true && isset($footer['value'])) {
                echo '<div>' . nl2br($footer['value']) . '</div>';
            }
            $disp_mem = $this->rrpreference->getPreferences('rr_display_memory_usage');
            if (isset($disp_mem['status']) && (boolean)$disp_mem['status'] === true) {
                echo '<div>' . echo_memory_usage() . '</div>';
            }
            ?>
        </div>

    </footer>
</div>


<div id="spinner" class="spinner hidden ">
    <i class="fa fa-spinner fa-spin fa-3x fa-fw text-danger"></i>
    <span class="sr-only">Loading...</span>
</div>


<a href="javascript:" id="return-to-top"><i class="fa fa-arrow-up largeicon"></i></a>

<div style="display: none">
    <input type="hidden" name="baseurl" value="<?php echo base_url(); ?>">
    <input type="hidden" name="csrfname" value="<?php echo $this->security->get_csrf_token_name(); ?>">
    <input type="hidden" name="csrfhash" value="<?php echo $this->security->get_csrf_hash(); ?>">
</div>


<div id="languageset" class="reveal tiny" data-reveal>
    <h4><?php echo lang('rr_langchange'); ?></h4>

    <form action="<?php echo $base_url . 'ajax/changelanguage/'; ?>" method="POST">
        <label>
            <select name="changelanguage">
                <?php
                $selset = false;
                foreach ($langs as $key => $value) {
                    if ($key === MY_Controller::getLang()) {
                        echo '<option value="' . $value['path'] . '" selected="selected">' . strtoupper($key) . '</option>';
                    } else {
                        echo '<option value="' . $value['path'] . '">(' . $key . ') ' . $value['val'] . '</option>';
                    }
                }
                ?>
            </select>
        </label>
    </form>
    <button class="close-button" data-close aria-label="Close modal" type="button">
        <span aria-hidden="true">&times;</span>
    </button>

</div>

<div id="sideicons">
    <?php

    echo implode('', $sideicons);

    ?>
</div>

<?php
//echo '<script type="text/javascript" src="' . $base_url . 'js/jquery.js"></script>' . PHP_EOL;
//echo '<script type="text/javascript" src="' . $base_url . 'js/foundation.js"></script>' . PHP_EOL;
echo '<script type="text/javascript" src="' . $base_url . 'js/f6-thirdpartylibs.min.a3db42c8.js"></script>' . PHP_EOL;

if (!empty($load_matrix_js)) {
    echo '<script type="text/javascript">';
    $this->load->view('reports/matrixsp_js_view');
    echo '</script>';
}

// load - need to have full url as it might be external one
if (!empty($jsAddittionalFiles) && is_array($jsAddittionalFiles)) {
    foreach ($jsAddittionalFiles as $jsPath) {
        echo '<script type="text/javascript" src="' . $jsPath . '"></script>' . PHP_EOL;
    }
}


if (!$loggedin) {
    $datalogin = array();
    if (!empty($showloginform)) {
        $datalogin['showloginform'] = $showloginform;
        $this->load->view('loginform_view', $datalogin);
    } else {
        $this->load->view('loginform_view');
    }
    $t = 'id="duo_form"';
    echo form_open(base_url() . 'authenticate/dologin', $t);
    echo form_close();
}
?>
<script>
    var Jagger = {
        base_url: '<?php echo $base_url;?>',
        csrfname: '<?php echo $this->security->get_csrf_token_name();?>',
        csrfhash: '<?php echo $this->security->get_csrf_hash();?>',
        lang: '<?php echo MY_Controller::getLang(); ?>',
        dictionary: {}
    }
</script>
<div id="malert" data-reveal class="reveal small"></div>
<div id="helpermodal" data-reveal class="reveal small"></div>
<?php
// load local final js

echo '<script type="text/javascript" src="' . $base_url . 'js/local-f6.min.dc1c662c.js"></script>' . PHP_EOL;

// raw js from array
if (!empty($rawJs) && is_array($rawJs)) {
    echo '<script>';

    foreach ($rawJs as $v) {
        echo $v . PHP_EOL;
    }


    echo '</script>';
}
?>


<script>
    $(document).foundation();
    $('.title-bar').on('sticky.zf.stuckto:top', function () {
        //     $(this).addClass('shrink');
    }).on('sticky.zf.unstuckfrom:top', function () {
        //    $(this).removeClass('shrink');
    })
</script>


</body>
</html>
