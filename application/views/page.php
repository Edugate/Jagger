<?php
$loggedin = $this->j_auth->logged_in();
$isAdministrator = FALSE;
if($loggedin)
{
   $isAdministrator = (boolean) $this->j_auth->isAdministrator();
}
$pageTitle = $this->config->item('pageTitlePref');
$colorTheme = $this->config->item('colortheme');
if(empty($colorTheme))
{
   $colorTheme = 'default';
}
$base_url = base_url();
$pageTitle .= $this->title;
$jquerybubblepopupthemes = $base_url.'styles/jquerybubblepopup-themes';
?>
<!DOCTYPE html>
<!--[if lt IE 7]> <html lang="<?php echo MY_Controller::getLang(); ?>" class="no-js ie6 oldie"> <![endif]-->
<!--[if IE 7]>    <html lang="<?php echo MY_Controller::getLang(); ?>" class="no-js ie7 oldie"> <![endif]-->
<!--[if IE 8]>    <html lang="<?php echo MY_Controller::getLang(); ?>" class="no-js ie8 oldie"> <![endif]-->
<!--[if gt IE 8]><!-->
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
        <link rel="apple-touch-icon" href="<?php echo $base_url; ?>images/touch-icon-iphone.png" />
        <link rel="apple-touch-icon" sizes="72x72" href="<?php echo $base_url; ?>images/touch-icon-ipad.png" />
        <link rel="apple-touch-icon" sizes="57x57" href="<?php echo $base_url; ?>images/apple-touch-icon-57x57-precomposed.png" />
        <link rel="apple-touch-icon" sizes="114x114" href="<?php echo $base_url; ?>images/apple-touch-icon-114x114-precomposed.png" />
        <link rel="apple-touch-icon" sizes="144x144" href="<?php echo $base_url; ?>images/apple-touch-icon-114x114-precomposed.png" />
        <link rel="stylesheet" type="text/css" href="<?php echo $base_url; ?>styles/jquery-ui.css"/>
        <?php
        
        echo '<link rel="stylesheet" type="text/css" href="' . $base_url . 'styles/'.$colorTheme.'.css" />';
        echo '<link rel="stylesheet" type="text/css" href="' . $base_url . 'styles/jquery.jqplot.min.css" />';
        echo '<link rel="stylesheet" type="text/css" href="' . $base_url . 'styles/jquery-bubble-popup-v3.css" />';     
        echo '<link rel="stylesheet" type="text/css" href="' . $base_url . 'styles/idpselect.css" />';
        echo '<script src="' . $base_url . 'js/modernizr-2.0.6.min.js"></script>';

        
        ?>

    </head>
    <body class="clearfix">
        <!--[if lt IE 7]>
                    <p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to improve your experience.</p>
                <![endif]-->
        <?php
        $iscookieconsent = $this->rrpreference->getPreferences('cookieConsent');
        if(isset($iscookieconsent['status']) && (boolean) $iscookieconsent['status'] === TRUE && isset($iscookieconsent['value']))
        {
            $this->load->helper('cookie');
            $cookieaccepted = get_cookie('cookieAccept');
            if(empty($cookieaccepted) or $cookieaccepted != 'accepted')
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
        ?>
        <div id="toppanel">
                                    <span id="logo">
                            <?php
                            echo '<a href="' . $base_url . '"><img src="' . $base_url . 'images/' . $this->config->item('site_logo') . '" alt="Logo"/></a>';
                            echo "\n";
                            ?>
                        </span> 
                <?php
                if ($loggedin)
                {
            echo '<div style="display: inline-block">';
                    echo '<span class="mobilehidden">' . lang('urloggedas') . '</span> <b> <a href="'.$base_url.'manage/users/show/'.base64url_encode($this->j_auth->current_user()).'" class="profilelink">'. htmlentities($this->j_auth->current_user()). '</a></b>&nbsp;&nbsp;'  ;
                   if(empty(j_auth::$timeOffset))
                   {
                     echo ' <small>UTC+0</small>';
                   }
                   elseif(j_auth::$timeOffset > 0)
                   {
                     echo ' <small>UTC+'.j_auth::$timeOffset/60/60 .'</small>';
                   }
                   else
                   {
                     echo ' <small>UTC'.j_auth::$timeOffset/60/60 .'</small>';
                   }

               echo '</div>';
?>
           <a href="<?php echo $base_url;?>auth/logout" class="logoutbutton" id="logout" style="float: right; margin-left: 10px; "><?php echo lang('btnlogout');?></a> 

<?php

                }
                else
                {
                    echo '<div style="display: inline-block">&nbsp;</div>';
                }
                ?>
       
            <div id="langicons">
                <?php
                  $langs = array(
                    'en' => array('path'=>'english','val'=>'english'),
                    'cs' => array('path'=>'cs','val'=>'čeština'),
                    'es' => array('path'=>'es','val'=>'español'),
                    'fr-ca' => array('path'=>'fr-ca','val'=>'français'),
                    'it' => array('path'=>'it','val'=>'italiano'),
                    'lt' => array('path'=>'lt','val'=>'lietuvos'),
                    'pl' => array('path'=>'pl','val'=>'polski'),
                    'pt' => array('path'=>'pt','val'=>'português'),
                   );
                ?>
                <div id="langchange">
                   <span id="langurl" style="display:none;"><?php echo $base_url.'ajax/changelanguage/';?></span>
                   <label>
                   <select  name="changelanguage">
                     <?php
                       $selset = false;
                       foreach($langs as $key=>$value)
                       {
                          if($key === MY_Controller::getLang())
                          {
                              echo '<option value="'.$value['path'].'" selected="selected">'.strtoupper($key).'</option>';
                          }
                          else
                          {
                              echo '<option value="'.$value['path'].'">('.$key.') '.$value['val'].'</option>';

                          }
                       }
                     ?>
                   </select>
                   </label>
                </div>
            </div>
        </div>
        <?php
         if(!$loggedin && empty($showloginform))
         {
        ?>
        <form id="login_link" action="#" method="get">
              <button id="loginlink"  type="button" class="loginbutton"><?php echo lang('toploginbtn'); ?></button>
        </form>
                <?php
         }
                ?>
        <div id="container">
            <div class="header-container">
                <header class="wrapper clearfix" role="banner">
                    <div class="header-top clearfix" style="text-align: right;">
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
           if(!empty($showhelp) && $showhelp === TRUE)
           {
              echo '<a href="'.base_url().'ajax/showhelpstatus" id="showhelps" class="helpactive"><img src="'.base_url().'images/icons/info.png" class="iconhelpshow" style="display:none"><img src="'.base_url().'images/icons/info.png" class="iconhelpcross"></a>';
           }
           else
           {
              echo '<a href="'.base_url().'ajax/showhelpstatus" id="showhelps" class="helpinactive"><img src="'.base_url().'images/icons/info.png" class="iconhelpshow"><img src="'.base_url().'images/icons/info.png" class="iconhelpcross" style="display:none"></a>';
           }
                        ?>
                        <!-- menu -->
                     
                        <nav>
                            <a class="toggleMenu" href="#">Menu</a>

                            <ul class="nav">
                                <li><a href="<?php echo $base_url; ?>"><img src="<?php echo $base_url; ?>images/icons/home.png" alt="home"/></a></li>
                                <li><a href="<?php echo $base_url; ?>federations/manage"><?php echo lang('federations'); ?></a>
                                    <ul>
                                        <li><a href="<?php echo $base_url; ?>federations/manage"><?php echo lang('rr_list'); ?></a></li>
                                        <li><a href="<?php echo $base_url; ?>federations/federation_registration"><?php echo lang('register'); ?></a></li>
                                        <li><a href="<?php echo $base_url; ?>manage/fedcategory/show"><?php echo lang('rrfedcatslist'); ?></a></li>
                                    </ul>
                                </li>
                                <li><a href="<?php echo $base_url; ?>providers/idp_list/show"><?php echo lang('identityproviders'); ?></a>
                                    <ul>
                                        <li><a href="<?php echo $base_url; ?>providers/idp_list/show"><?php echo lang('rr_list'); ?></a></li>
                                        <li><a href="<?php echo $base_url; ?>providers/idp_registration"><?php echo lang('register'); ?></a></li>

                                    </ul>
                                </li>
                                <li><a href="<?php echo $base_url; ?>providers/sp_list/show"><?php echo lang('serviceproviders'); ?></a>
                                    <ul>
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
                                            echo '<span class="inqueue">' . $inqueue . '</span>';
                                        }
                                        ?>
                                    </a></li>
                                <li><a href="<?php echo $base_url; ?>"><?php echo lang('general'); ?></a>
                                    <ul>
                                        <?php
                                           if($isAdministrator)
                                           {
                                              echo '<li><a href="'.$base_url.'smanage/reports">'.lang('sys_menulink').'</a></li>';
                                           }
                                        ?>
                                        <li><a href="<?php echo $base_url; ?>manage/coc/show"><?php echo lang('entcats_menulink'); ?></a></li>
                                        <li><a href="<?php echo $base_url; ?>attributes/attributes/show"><?php echo lang('rr_attr_defs'); ?></a></li>
                                       <?php
                                        if($isAdministrator)
                                        {
                                        ?>
                                        <li><a href="<?php echo $base_url; ?>manage/importer"><?php echo lang('rr_meta_importer'); ?></a></li>
                                        <li><a href="<?php echo $base_url; ?>manage/users/showlist"><?php echo lang('rr_users'); ?> &nbsp;&gt;</a>
                                            <ul>
                                                <li><a href="<?php echo $base_url; ?>manage/users/showlist"><?php echo lang('rr_users_list'); ?></a>
                                                <li><a href="<?php echo $base_url; ?>manage/users/add"><?php echo lang('rr_newuser'); ?></a></li>
                                                <li><a href="<?php echo $base_url; ?>manage/users/remove"><?php echo lang('rr_rmuser'); ?></a></li>
                                            </ul>
                                        </li>
                                       <?php
                                       }
                                      ?>
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
                            if(!$loggedin)
                            {
                                $datalogin = array();
                                if(!empty($showloginform))
                                {
                                   $datalogin['showloginform'] = $showloginform;
                                   $this->load->view('auth/login',$datalogin);
                                }
                                else
                                {
                                      $this->load->view('auth/login');
                                 }

                            }
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
                    $footer = $this->rrpreference->getPreferences('pageFooter');
                    if(isset($footer['status']) && (boolean) $footer['status'] === TRUE && isset($footer['value']))
                    {
                          echo '<small>'.$footer['value'].'</small><br />';
                    }
                    $disp_mem = $this->rrpreference->getPreferences('rr_display_memory_usage');
                    if (isset($disp_mem['status']) && (boolean) $disp_mem['status'] === TRUE )
                    {
                        echo echo_memory_usage();
                    }
                    ?>

                </footer>
            </div>

        <div style="height: 50px;"></div>
        <div id="spinner" class="spinner" style="display:none;">
            <img id="img-spinner" src="<?php echo $base_url; ?>images/spinner1.gif" alt="<?php echo lang('loading');?>"/>
        </div>
        <div style="display: none">
             <input type="hidden" name="baseurl" value="<?php echo base_url(); ?>">
             <input type="hidden" name="csrfname" value="<?php echo $this->security->get_csrf_token_name(); ?>">
             <input type="hidden" name="csrfhash" value="<?php echo $this->security->get_csrf_hash(); ?>">
        </div>
        
        <button id="jquerybubblepopupthemes" style="display:none;" value="<?php echo $jquerybubblepopupthemes; ?>"></button> 
        <script src="<?php echo $base_url;?>js/jquery-min.js"></script>
        <script>window.jQuery || document.write('<script src="<?php echo $base_url; ?>js/jquery-min.js">\x3C/script>')</script>
        <script src="<?php echo $base_url; ?>js/jquery-migrate-1.2.0.js"></script>
        <script src="<?php echo $base_url; ?>js/jquery-ui.min.js"></script>
        <script src="<?php echo $base_url; ?>js/bootstrap.min.js"></script>

        <script type="text/javascript" src="<?php echo $base_url; ?>js/jquery.uitablefilter.js"></script>
        <?php
        echo '<script type="text/javascript" src="' . $base_url . 'js/jquery.jqplot.min.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/jqplot.dateAxisRenderer.min.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/jqplot.cursor.min.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/jqplot.highlighter.min.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/jquery.tablesorter.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/jquery.inputfocus-0.9.min.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/jquery-bubble-popup-v3.min.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/jquery.simplemodal.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/locals-v2.js"></script>';

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
