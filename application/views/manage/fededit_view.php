<?php

if (!empty($javascript)) {
    echo $javascript;
}
if (!empty($error_message)) {
    echo '<div alertdata-alert class="alert-box alert">' . $error_message . '</div>';
}
if (!empty($success_message)) {
    echo '<div alertdata-alert class="alert-box success">' . $success_message . '</div>';
    $redirectto = base_url() . 'federations/manage/show/' . $encodedfedname;
    ?>
    <script type="text/javascript">
        function Redirect() {
            window.location.href = "<?php echo $redirectto;?>";
        }
        setTimeout('Redirect()', 1000);
    </script>

<?php
}

if (!empty($form)) {
    echo $form;
}

