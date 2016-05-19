<?php

$myLang = MY_Controller::getLang();
$activemenu = MY_Controller::$menuactive;
$siteLogo = $this->config->item('site_logo');
if (empty($siteLogo)) {
    $siteLogo = 'logo-default.png';
}
$logoSrc = $base_url . 'images/' . $siteLogo;
$homeUrl = $base_url;
if ($loggedin) {
    $homeUrl = $base_url . 'home';
}

$topbarArray['logo'] = array(
    'img'  => $logoSrc,
    'link' => $homeUrl,
);

$topbarArray['left']= array(
);


$topbarArray['right'][] = array(
    'name'     => strtoupper($myLang),
    'link'     => null,
    'linkprop' => ' class="button"  data-open="languageset"',
    'sub'      => null
);
if ($loggedin) {
    $topbarArray['right'][] = array(
        'name' => '<span id="qcounter" class="alert  badge" data-jagger-src="' . $base_url . 'reports/awaiting/dashz">0</span>',
        'link' => '' . $base_url . 'reports/awaitinglist'
    );
    $topbarArray['right'][] = array(
        'name'     => lang('btnlogout'),
        'link'     => '' . $base_url . 'auth/logout',
        'linkprop' => 'class="button alert logoutbutton userlogout" jagger-data-logout="' . $shibLogoutUri . '"'
    );

    $adminSubmenu = array(
        array(
            'name' => lang('addons_menulink'),
            'link' => $base_url . 'tools/addontools/show',
        ),
        array(
            'name' => lang('rrfedcatslist'),
            'link' => $base_url . 'manage/fedcategory/show',
        ),
        array(
            'name' => lang('entcats_menulink'),
            'link' => $base_url . 'manage/ec/show',
        ),
        array(
            'name' => lang('regpols_menulink'),
            'link' => $base_url . 'manage/regpolicy/show'
        ),
        array(
            'name' => lang('rr_attr_defs'),
            'link' => $base_url . 'attributes/attributes/show"'
        )

    );
    if ($isAdministrator) {
        $adminSubmenu[] = array(
            'name' => lang('sys_menulink'),
            'link' => $base_url . 'smanage/reports'
        );
        $adminSubmenu[] = array(
            'name' => lang('globalconf_menulink'),
            'link' => $base_url . 'smanage/sysprefs/show',
        );
        $featenabled = $this->config->item('featenable');
        if (is_array($featenabled) && array_key_exists('tasks', $featenabled) && $featenabled['tasks'] === true) {
            $adminSubmenu[] = array(
                'name' => lang('tasks_menulink'),
                'link' => $base_url . 'smanage/taskscheduler/tasklist',
            );

        }
        $adminSubmenu[] = array(
            'name' => lang('rr_meta_importer'),
            'link' => $base_url . 'manage/importer'
        );
        $adminSubmenu[] = array(
            'name' => lang('rr_users'),
            'link' => $base_url . 'manage/users/showlist'
        );
        $adminSubmenu[] = array(
            'name' => lang('rr_articlesmngmt'),
            'link' => $base_url . 'manage/spage/showall'
        );
        $adminSubmenu[] = array(
            'name' => lang('rr_mailtemplmngmt'),
            'link' => $base_url . 'manage/mailtemplates/showlist'
        );
    }


    $topbarArray['left'] = array(
        array(
            'name'   => lang('federations'),
            'link'   => $base_url . 'federations/manage',
            'sub'    => null,
            'active' => (bool)($activemenu === 'fed'),
        ),
        array(
            'name'   => lang('identityproviders'),
            'link'   => $base_url . 'providers/idp_list/showlist',
            'active' => (bool)($activemenu === 'idps'),
            'sub'    => null
        ),
        array(
            'name'   => lang('serviceproviders'),
            'link'   => $base_url . 'providers/sp_list/showlist',
            'active' => (bool)($activemenu === 'sps'),
            'sub'    => null
        ),
        array(
            'name'   => lang('register'),
            'active' => (bool)($activemenu === 'reg'),
            'sub'    => array(
                array(
                    'name' => lang('identityprovider'),
                    'link' => $base_url . 'providers/idp_registration',
                    'sub'  => null
                ),
                array(
                    'name' => lang('serviceprovider'),
                    'link' => $base_url . 'providers/sp_registration',
                    'sub'  => null,
                ),
                array(
                    'name' => lang('idpspprovider'),
                    'link' => $base_url . 'providers/idpsp_registration',
                    'sub'  => null
                ),
                array(
                    'name' => lang('rr_federation'),
                    'link' => $base_url . 'federations/fedregistration',
                    'sub'  => null
                )
            )
        ),
        array(
            'name'   => lang('rr_administration'),
            'active' => (bool)($activemenu === 'admins'),
            'sub'    => $adminSubmenu
        )
    );


} else {
    $logged = $this->session->userdata('logged');
    $partialLogged = $this->session->userdata('partiallogged');
    if ($logged !== 1 && $partialLogged === 1) {
        $topbarArray['right'][] = array(
            'name'     => lang('toploginbtn'),
            'link'     => $base_url . 'authenticate/getloginform',
            'linkprop' => 'class="button alert autoclick" id="loginbtn"'
        );

    } else {
        $topbarArray['right'][] = array(
            'name'     => lang('toploginbtn'),
            'link'     => $base_url . 'authenticate/getloginform',
            'linkprop' => ' class="button alert" id="loginbtn"'
        );
    }
}


echo generateTopBar($topbarArray);


$y = '
<div data-sticky-container>
    <div class="title-bar" data-responsive-toggle="topbar-menu" data-hide-for="medium" data-sticky>
        <button class="menu-icon" type="button" data-toggle></button>
        <div class="title-bar-title">Menu</div>
    </div>

    <div id="topbar-menu" class="top-bar" data-sticky data-options="marginTop:0;">


        <div class="title-bar-left">

            <div class="logo-wrapper hide-for-small-only">


                
                <span class="top-bar-title logo"><a href="' . $homeUrl . '" class="sitelogo"><img src="' . $logoSrc . '" alt="Logo"/></a></span>;
                


            </div>


        </div>
        <div class="top-bar-right">
            <ul class="dropdown menu align-right" data-dropdown-menu>
                <li>
                    <a>Item 1</a>
                    <ul class="menu">
                        <li><a href="#">Item 1A</a></li>
                        <li>
                            <a href="#">Item 1B</a>
                            <ul class="menu">
                                <li><a href="#">Item 1B i</a></li>
                                <li><a href="#">Item 1B ii</a></li>
                                <li>
                                    <a href="#">Item 1B iii</a>
                                    <ul class="menu">
                                        <li><a href="#">Item 1B iii alpha</a></li>
                                        <li><a href="#">Item 1B iii omega</a></li>
                                    </ul>
                                </li>
                                <li>
                                    <a href="#">Item 1B iv</a>
                                    <ul class="menu">
                                        <li><a href="#">Item 1B iv alpha</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                        <li><a href="#">Item 1C</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#">Item 2</a>
                    <ul class="menu">
                        <li><a href="#">Item 2A</a></li>
                        <li><a href="#">Item 2B</a></li>
                    </ul>
                </li>
                <li><a href="#">Item 3</a></li>
                <li><a href="#">Item 4</a></li>
            </ul>


        </div>
    </div>
</div>';


