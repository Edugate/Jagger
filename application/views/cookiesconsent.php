<div id="cookiesinfo" class="row expanded">
    <div id="lcookiesinfo" class="alert-box warning small-12 columns">
        <?php
        echo $value;
        ?>
    </div>
    <div id="rcookiesinfo" class="large-12 columns text-right">
        <?php
        echo '<button type="button" class="pCookieAccept button small success" value="' . base_url('ajax/consentCookies') . '">' . lang('rr_readnotice') . '</button>';
        ?>
    </div>
    <div id="cookiefoot"></div>
</div>
