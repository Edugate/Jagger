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

if (isset($shib['enabled']) && $shib['enabled'] === TRUE) {
    if (isset($shib['loginapp_uri'])) {
        $shib_url = base_url() . $shib['loginapp_uri'];
        $fedenabled = TRUE;
    } else {
        log_message('error', 'Federation login enabled but fedurl not found');
    }
} elseif (isset($ssphp['enabled']) && $ssphp['enabled'] === TRUE) {
    $shib_url = base_url() . 'auth/ssphpauth';
    $fedenabled = TRUE;
}

$fedloginbtn = $this->config->item('fedloginbtn');
if ($fedloginbtn === null) {
    $fedloginbtn = lang('federated_access');
}


if ($fedenabled || $oidcEnabled) {
    echo '<div id="loginform" class="row reveal-modal medium" data-reveal>';

    echo '<div id="loginresponse" class="alert-box alert hidden" ></div>';

    echo '<div class="large-6 columns">';
    echo form_open($base . 'authenticate/dologin');
    echo '<div class="row">';
    echo '<h4 class="small-12 columns end text-center loginheader">' . lang('loginwithlocal') . '</h4>';
    echo '</div>';
    echo '<div class="row usernamerow hidden">';
    echo '<div class="medium-3 columns medium-text-right"> <label for="username" class="inline">' . lang('rr_username') . '</label> </div> <div class="medium-9 columns"> 
             <input type="text" id="username" name="username"></div>';
    echo '</div>';
    echo '<div class="row passwordrow hidden" >';
    echo '<div class="medium-3 columns medium-text-right"> <label for="password" class="inline">' . lang('rr_password') . '</label> </div> <div class="medium-9 columns"> 
             <input type="password" id="password" name="password" ><div id="capswarn" class="hidden"><small class="error" >' . lang('rr_capslockon') . '</small></div></div>';
    echo '</div>';
    echo '<div class="row">';
    echo '<div class="small-12 large-9 end columns large-push-3 small-text-center large-text-left"><button type="submit" class="button small">' . lang('loginsubmit') . '</button></div>';
    echo '</div>';
    echo '<div class="row secondfactorrow hidden">';

    echo '</div>';
    echo '';
    echo form_close();
    echo '</div>';


    echo '<div class="large-6 columns end">';
    if ($fedenabled) {

        echo '<div class="small-12 columns end text-center"><a href="' . $shib_url . '" id="fedlogin" class="button small fedlogin"><span></span>' . html_escape($fedloginbtn) . '</a></div>';

    }
    if ($oidcEnabled) {
        echo '<div class="small-12 columns end text-center"><h4 class="loginheader small-12 columns end text-center">OpenId Connect</h4></div>';
        echo '<div class="small-12 columns end text-center">';
        foreach($oidcOps as $key => $oidcOp){
            $btnClass = '';
            if(array_key_exists('btnclass',$oidcOp)){
                $btnClass = $oidcOp['btnclass'];
            }
            echo '<a href="'.base_url('oidcauth/authn').'" class="button oidclink tiny split '.$btnClass.'" data-jagger-oidc="'.$key.'"><span></span>'.$oidcOp['name'].'</a>';
        }
        echo '</div>';

    }
    echo '</div>';
    echo '<a id="resetloginform" class="close-reveal-modal small  has-tip" data-tooltip aria-haspopup="true"  title="Close and reset form">&#215;</a>';

    echo '</div>';
} else {
    echo '<div id="loginform" class="reveal-modal row small" data-reveal>';
    $column_class = 'large-12';
    echo '<div id="loginresponse" class="alert-box alert" style="display: none"></div>';
    echo '<div class="' . $column_class . ' columns">';
    echo form_open($base . 'authenticate/dologin');
    echo '<div class="medium-12 columns">';
    echo '<h4 class="small-12 columns end text-center loginheader">' . lang('loginwithlocal') . '</h4>';
    echo '<div class="small-3 columns"> <label for="username" class="right inline">' . lang('rr_username') . '</label> </div> <div class="small-9 columns"> 
             <input type="text" id="username" name="username"></div>';
    echo '</div>';
    echo '<div class="medium-12 columns">';
    echo '<div class="small-3 columns"> <label for="password" class="right inline">' . lang('rr_password') . '</label> </div> <div class="small-9 columns"> 
             <input type="password" id="password" name="password"></div>';
    echo '</div>';
    echo '<div class="large-12 columns">';
    echo '<div class="small-3 columns"></div>';
    echo '<div class="small-9 columns"><button type="submit" class="button small">' . lang('loginsubmit') . '</button></div>';
    echo '</div>';
    echo form_close();
    echo '</div>';
    echo '<a id="resetloginform" class="close-reveal-modal small  has-tip" data-tooltip aria-haspopup="true"  title="Close and reset form">&#215;</a>';
    echo '<div class="small-12 column"><iframe id="duo_iframe" width="620" height="330" frameborder="0"></iframe></div>';
    echo '</div>';
}

