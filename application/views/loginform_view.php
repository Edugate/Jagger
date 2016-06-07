<?php

/**
 * new login form using foundation
 */
$base = base_url();
$shib = $this->config->item('Shibboleth');
$ssphp = $this->config->item('simplesamlphp');
$oidc = $this->config->item('oidc_enabled');
$oidcOps = $this->config->item('oidc_ops');
$oidcEnabled = false;
if ($oidc === true && is_array($oidcOps) && count($oidcOps) > 0) {
    $oidcEnabled = true;
}
$fedenabled = false;
$shib_url = null;

if (isset($shib['enabled']) && $shib['enabled'] === true) {
    if (isset($shib['loginapp_uri'])) {
        $shib_url = base_url() . $shib['loginapp_uri'];
        $fedenabled = true;
    } else {
        log_message('error', 'Federation login enabled but fedurl not found');
    }
} elseif (isset($ssphp['enabled']) && $ssphp['enabled'] === true) {
    $shib_url = base_url() . 'auth/ssphpauth';
    $fedenabled = true;
}

$fedloginbtn = $this->config->item('fedloginbtn');
if ($fedloginbtn === null) {
    $fedloginbtn = lang('federated_access');
}


if ($fedenabled || $oidcEnabled) {
    echo '<div id="loginform" class="row reveal medium" data-reveal>';

    echo '<div id="loginresponse" class="callout alert hidden" ></div>';

    echo '<div class="large-6 columns">';
    echo form_open($base . 'authenticate/dologin');
    echo '<div class="row">';
    echo '<h5 class="small-12 columns end text-center loginheader">' . lang('loginwithlocal') . '</h5>';
    echo '</div>';

    echo '<div class="row usernamerow hidden">';

    echo '<div class="input-group">';
    echo '<span class="input-group-label"><i class="fa fa-user"></i></span>';
    echo '<input class="input-group-field" type="text" id="username" name="username">';
    echo '</div>';

    echo '</div>';


    echo '<div class="row passwordrow" >';

    echo '<div class="input-group">';
    echo '<span class="input-group-label"><i class="fa fa-lock"></i></span>';
    echo '<input class="input-group-field" type="password" id="password" name="password">';
    echo '</div>';
    echo '</div>';
    echo '<div id="capswarn" class="row hidden"><div class="label alert" >' . lang('rr_capslockon') . '</div></div>';

    echo '<div class="row">';
    echo '<div class="smallf-12  fcolumns text-center"><button type="submit" class="button expanded">' . lang('loginsubmit') . '</button></div>';
    echo '</div>';
    echo '<div class="row secondfactorrow hidden">';

    echo '</div>';
    echo '';
    echo form_close();
    echo '</div>';


    echo '<div class="large-6 columns end">';
    if ($fedenabled) {
        echo '<div class="row show-for-medium">&nbsp;</div>';
echo '<div class="row text-center hide-for-medium"><hr /></div>';

        echo '<div class="small-12 columns end text-center"><a href="' . $shib_url . '" id="fedlogin" class="button expanded fedlogin"><span></span>' . html_escape($fedloginbtn) . '</a></div>';

    }
    if ($oidcEnabled) {
        echo '<div class="small-12 columns end text-center"><hr /></div>';
        echo '<div class="small-12 columns end text-center">';
        foreach ($oidcOps as $key => $oidcOp) {
            $btnClass = '';
            if (array_key_exists('btnclass', $oidcOp)) {
                $btnClass = $oidcOp['btnclass'];
            }
            echo '<a href="' . base_url('oidcauth/authn') . '" class="button oidclink  split ' . $btnClass . '" data-jagger-oidc="' . $key . '"><span></span>' . $oidcOp['name'] . '</a>';
        }
        echo '</div>';

    }
    echo '</div>';

    echo ' <button  id="resetloginform" class="close-button" data-close aria-label="Close reveal" type="button">
    <span aria-hidden="true">&times;</span>
  </button>';
    echo '</div>';
} else {
    echo '<div id="loginform" class="reveal row small" data-reveal>';
    $column_class = 'large-12';
    echo '<div id="loginresponse" class="alert-box alert" style="display: none"></div>';
    echo '<div class="' . $column_class . ' columns">';
    echo form_open($base . 'authenticate/dologin');
    echo '<div class="medium-12 columns">';
    echo '<h5 class="small-12 columns end text-center loginheader">' . lang('loginwithlocal') . '</h5>';
    echo '<div class="small-3 columns"> <label for="username" class="right inline">' . lang('rr_username') . '</label> </div> <div class="small-9 columns"> 
             <input type="text" id="username" name="username"></div>';
    echo '</div>';
    echo '<div class="medium-12 columns">';
    echo '<div class="small-3 columns"> <label for="password" class="right inline">' . lang('rr_password') . '</label> </div> <div class="small-9 columns"> 
             <input type="password" id="password" name="password"></div>';
    echo '</div>';
    echo '<div class="large-12 columns">';
    echo '<div class="small-3 columns"></div>';
    echo '<div class="small-9 columns"><button type="submit" class="button">' . lang('loginsubmit') . '</button></div>';
    echo '</div>';
    echo form_close();
    echo '</div>';

    echo ' <button  id="resetloginform" class="close-button" data-close aria-label="Close reveal" type="button">
    <span aria-hidden="true">&times;</span>
  </button>';

    echo '<div class="small-12 column"><iframe id="duo_iframe" width="620" height="330" frameborder="0"></iframe></div>';
    echo '</div>';
}

