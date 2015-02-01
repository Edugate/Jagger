<div class="sticky">
    <nav class="top-bar docs-bar" data-topbar data-options="sticky_on: large">
        <?php
        ?>
        <ul class="title-area">
            <li class="menu">
                <?php
                $siteLogo = $this->config->item('site_logo');
                if(empty($siteLogo))
                {
                   $siteLogo = 'logo-default.png';
                }
                echo '<a href="' . $base_url . '"><img src="' . $base_url . 'images/' . $siteLogo . '" alt="Logo" style="max-height: 40px; margin-top: 2px; padding-right: 5px;"/></a>';
                ?>

            </li>
            <li class="divider"></li>
            <!-- Remove the class "menu-icon" to get rid of menu icon. Take out "Menu" to just have icon alone -->
            <li class="toggle-topbar menu-icon"><a href="#"><span>Menu</span></a></li>
        </ul>
        <section class="top-bar-section">
            <!-- Right Nav Section -->
            <ul class="right">
                <?php
                if ($loggedin)
                {
                    echo '<li><a href="'.$base_url.'reports/awaiting"><sup id="qcounter" class="label alert tiny round" style="box-shadow: 0px 1px 5px #0a0a0a, inset 0px 1px 2px #bdbdbd;"></sup></a></li>';
                    echo '<li class="show-for-small-only"><a href="'.$base_url.'auth/logout" class="button alert logoutbutton tiny" id="logout">'.lang('btnlogout').'</a></li>';
                    
                    ?>
                    <?php
                }
                ?>
                <li class="has-form">

                    <?php
                    $k = MY_Controller::getLang();
                    echo '<a href="#" class="button full"  data-reveal-id="languageset">' .  strtoupper($k)  . '</a>';
                    ?>

                </li>

                <?php
                if ($loggedin)
                {
                ?>
                    <li class="has-dropdown">
                        <a href="#">
                            <?php 
                            echo '<img src="'.base_url().'images/jicons/male80.svg" class="jicon" style="height: 20px" title="'.htmlentities($user).'"/>';
                            ?></a>
                        <ul class="dropdown">
                            <li><a href="<?php echo $base_url . 'manage/users/show/' . base64url_encode($user) . ''; ?>"><?php echo lang('myprofile'); ?></a></li>
                            <li><a href="<?php echo $base_url . 'notifications/subscriber/mysubscriptions/' . base64url_encode($user) . ''; ?>"><?php echo lang('rrmynotifications'); ?></a></li>
                            <li class="show-for-medium-up"><a href="<?php echo $base_url; ?>auth/logout" class="alert" id="logout"><?php echo lang('btnlogout'); ?></a></li>
                        </ul>
                    </li>




                <?php
                }
                else
                {
                ?>
                    <li class="has-form">
                        <?php
                           if(((array_key_exists('logged',$_SESSION) && $_SESSION['logged'] === 0)  || !array_key_exists('logged',$_SESSION) ) && isset($_SESSION['partiallogged']) && $_SESSION['partiallogged'] === 1 )
                           {
                              ?>
                               <a href="<?php echo $base_url; ?>authenticate/getloginform" class="button alert autoclick"  id="loginbtn" ><?php echo lang('toploginbtn'); ?></a>
                        <?php
                           }
                        else {
                            ?>
                            <a href="<?php echo $base_url; ?>authenticate/getloginform" class="button alert"
                               id="loginbtn"><?php echo lang('toploginbtn'); ?></a>
                        <?php
                        }
                        ?>

                    </li>

                    <?php
                }
                
                ?>
            </ul>

            <!-- Left Nav Section -->

            <?php
               $activemenu = MY_Controller::$menuactive;
               $factive = '';
               $idpsactive = '';
               $spsactive = '';
               $adminsactive = '';
               $regactive = '';
               if($activemenu ==='fed')
               {
                   $factive = 'active';
               }
               elseif($activemenu === 'idps')
               {
                   $idpsactive ='active';
               }
               elseif($activemenu === 'sps')
               {
                   $spsactive = 'active';
               }
               elseif($activemenu === 'admins')
               {
                  $adminsactive = 'active';
               }
               elseif($activemenu === 'reg')
               {
                  $regactive = 'active';
               }


               $divider = '<li class="divider"></li>';
            ?>
            <ul class="left">
                <?php
                if ($loggedin)
                {
                    echo '<li class="'.$factive.'"><a href="'.$base_url.'federations/manage">'.lang('federations').'</a></li>';
                    echo $divider;
                    echo '<li class="'.$idpsactive.'"><a href="'.$base_url.'providers/idp_list/showlist">'.lang('identityproviders').'</a></li>';
                    echo $divider;
                    echo '<li class="'.$spsactive.'"><a href="'.$base_url.'providers/sp_list/showlist">'.lang('serviceproviders').'</a></li>';
                    echo $divider;
                    echo '<li class="'.$regactive.' has-dropdown">';
                    ?>
                        <a href="<?php echo $base_url; ?>"><?php echo lang('register'); ?></a>
                        <ul class="dropdown">
                            <li><a href="<?php echo $base_url; ?>providers/idp_registration"><?php echo lang('identityprovider'); ?></a></li>
                            <li><a href="<?php echo $base_url; ?>providers/sp_registration"><?php echo lang('serviceprovider'); ?></a></li>
                            <?php
                            if ($loggedin)
                            {
                                ?>
                                <li><a href="<?php echo $base_url; ?>federations/federation_registration"><?php echo lang('rr_federation'); ?></a></li>
                                <?php
                            }
                            ?>
                        </ul>
                    </li>
                    <li class="divider"></li>
                    <?php
                    echo '<li class="'.$adminsactive.' has-dropdown"><a href="'.$base_url.'">'.lang('rr_administration').'</a>';
                    ?>
                        <ul class="dropdown">
                            <?php
                            if ($isAdministrator)
                            {
                                echo '<li><a href="' . $base_url . 'smanage/reports">' . lang('sys_menulink') . '</a></li>';
                                echo '<li><a href="' . $base_url . 'smanage/sysprefs/show">' . lang('globalconf_menulink') . '</a></li>';

                            }
                            ?>
                            <li><a href="<?php echo $base_url; ?>tools/addontools/show"><?php echo lang('addons_menulink'); ?></a></li>
                            <li><a href="<?php echo $base_url; ?>manage/fedcategory/show"><?php echo lang('rrfedcatslist'); ?></a></li>
                            <li class="divider"></li>
                            <li><a href="<?php echo $base_url; ?>manage/ec/show"><?php echo lang('entcats_menulink'); ?></a></li>
                            <li><a href="<?php echo $base_url; ?>manage/regpolicy/show"><?php echo lang('regpols_menulink'); ?></a></li>
                            <li class="divider"></li>
                            <li><a href="<?php echo $base_url; ?>attributes/attributes/show"><?php echo lang('rr_attr_defs'); ?></a></li>
                            <?php
                            if ($isAdministrator)
                            {

                                echo '<li><a href="' . $base_url . 'manage/importer">' . lang('rr_meta_importer') . '</a></li>';
                                ?>
                                <li class="has-dropdown"><a href="<?php echo $base_url; ?>manage/users/showlist"><?php echo lang('rr_users'); ?></a>
                                    <ul class="dropdown">
                                        <li><a href="<?php echo $base_url; ?>manage/users/showlist"><?php echo lang('rr_users_list'); ?></a>
                                        <li><a href="<?php echo $base_url; ?>manage/users/add"><?php echo lang('rr_newuser'); ?></a></li>
                                        <li><a href="<?php echo $base_url; ?>manage/users/remove"><?php echo lang('rr_rmuser'); ?></a></li>
                                    </ul>
                                </li>
                                <?php
                                echo '<li><a href="' . $base_url . 'manage/spage/showall">' . lang('rr_articlesmngmt') . '</a></li>';
                                echo '<li><a href="' . $base_url . 'manage/mailtemplates/showlist">' . lang('rr_mailtemplmngmt') . '</a></li>';
                                
                            }
                            ?>

                        </ul>

                    </li>
                    <li class="divider"></li>

                    <?php
                }
                ?>
            </ul>
        </section>
    </nav>

</div>

