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
<html lang="en">
    <head>     
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width" />
        <?php
        echo '<title>' . $pageTitle . '</title>';
        echo '<link rel="stylesheet" type="text/css" href="' . $base_url . 'styles/themes/base/jquery.ui.all.css" />';

        echo '<link rel="stylesheet" type="text/css" href="' . $base_url . 'styles/themes/redmond/jquery.ui.all.css" />';

        echo '<link rel="stylesheet" type="text/css" href="' . $base_url . 'styles/themes/redmond/jquery-ui-1.8.17.custom.css" />';
        echo '<link rel="stylesheet" type="text/css" href="' . $base_url . 'styles/jquery.jqplot.min.css" />';
        ?>
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>

        <?php
        echo '<script type="text/javascript" src="' . $base_url . 'js/jquery.jqplot.min.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/jqplot.dateAxisRenderer.min.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/jqplot.cursor.min.js"></script>';
        echo '<script type="text/javascript" src="' . $base_url . 'js/jqplot.highlighter.min.js"></script>';
       
      
          //echo '<script type="text/javascript" src="' . $base_url . 'js/jqplot.pointLabels.min.js" />';
      

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
        echo '<link rel="stylesheet" type="text/css" href="' . $base_url . 'styles/screen.css" media="screen" />';

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

                <?php
                if ($this->j_auth->logged_in())
                {
                    echo '<span class="loggedin">';
                    echo "You are logged in as <b>" . $_SESSION['username'] . "</b>" . anchor(base_url() . "auth/logout", '<img src="' . base_url() . 'images/icons/external.png" title="Sign out"/>') . '<br /><br />';
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
                $curr = 'class="current"';
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
                        <li <?php echo $m_home; ?> ><a href="<?php echo base_url() ?>">Home</a>

                        </li>
                        <li <?php echo $m_federation; ?>><a href="<?php echo base_url(); ?>federations/manage">Federations</a>
                            <ul>
                                <li><a href="<?php echo base_url(); ?>federations/federation_registration">Register</a></li>
                            </ul>
                        </li>
                        <li <?php echo $m_idp ?>><a href="<?php echo base_url(); ?>providers/idp_list/show">Identity Providers</a>
                            <ul><?php
                echo $current_idp_menu;
                ?>

                                <li><a href="<?php echo base_url(); ?>providers/idp_list/show">List</a></li>
                                <li><a href="<?php echo base_url(); ?>providers/idp_registration">Register</a></li>

                            </ul>
                        </li>
                        <li <?php echo $m_sp ?>><a href="<?php echo base_url(); ?>providers/sp_list/show">Service Providers</a>
                            <ul><?php
                            echo $current_sp_menu;
                            ?>
                                <li><a href="<?php echo base_url(); ?>providers/sp_list/show">List</a></li>
                                <li><a href="<?php echo base_url(); ?>manage/attribute_requirement/sp">Attribute Requirement</a></li>
                                <li><a href="<?php echo base_url(); ?>providers/sp_registration">Register</a></li>
                            </ul>
                        </li>
                        <li <?php echo $m_register; ?>><a href="<?php echo base_url(); ?>">Register</a>
                            <ul><li><a href="<?php echo base_url(); ?>providers/idp_registration">Identity Provider</a></li>
                                <li><a href="<?php echo base_url(); ?>providers/sp_registration">Service Provider</a></li>
                                <li><a href="<?php echo base_url(); ?>federations/federation_registration">Federation</a></li>
                            </ul>
                        </li>

                        <li  <?php echo $m_awaiting; ?>><a href="<?php echo base_url(); ?>reports/awaiting">Queue
                <?php
                if (!empty($inqueue))
                {
                    echo "<span class=\"inqueue\">" . $inqueue . "</span>";
                }
                ?>
                            </a></li>
                        <li <?php echo $m_general; ?>><a href="<?php echo base_url(); ?>">General</a>
                            <ul>
                                <li><a href="<?php echo base_url(); ?>attributes/attributes/show">Attribute definitions</a></li>
                                <li><a href="<?php echo base_url(); ?>manage/importer">Metadata importer</a></li>
                                <li><a href="<?php echo base_url(); ?>manage/users/showlist">Users &nbsp;&gt;</a>

                                    <ul>
                                        <li><a href="<?php echo base_url(); ?>manage/users/showlist">Users list</a>
                                        <li><a href="<?php echo base_url(); ?>manage/users/add">New user</a></li>
                                        <li><a href="<?php echo base_url(); ?>manage/users/remove">Remove user</a></li>

                                    </ul>
                                </li>
                            </ul>
                        </li>
                        <li><a href="<?php echo base_url(); ?>">Help</a></li>
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

            <div id="wrapper" class="span-21 append-1 prepend-1"  class="block" <?php echo $height100 ?> >
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
