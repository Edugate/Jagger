<?php
$redirect_to = base_url();
$shibcnf = $this->config->item('Shibboleth');
?>
    <html>
    <head>
        <script type="text/javascript">
            function Redirect() {
                window.location.href = "<?php echo $redirect_to;?>";
            }
            setTimeout('Redirect()', 1000);
        </script>


    </head>
    <body>
    <noscript>
        JavaScript is not enabled in your browser.
    </noscript>
    <?php
    if (isset($shibcnf['enabled']) && $shibcnf['enabled'] === TRUE && isset($shibcnf['logout_uri'])) {
        ?>
        <iframe style="visibility:hidden" frameborder=0 marginheight=0 marginwidth=0 scrolling=no
                src="<?php echo $shibcnf['logout_uri']; ?>"></iframe>
        <?php
    }
    ?>
    </body>
    <html>
<?php
