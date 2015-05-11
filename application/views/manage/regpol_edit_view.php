<?php
$errors_v = validation_errors('<div>', '</div>');
if (!empty($errors_v)) {
    echo '<div data-alert class="alert-box alert">'.$errors_v.'</div>';
}
if(!empty($success_message))
{
    echo '<div data-alert class="alert-box success">'.$success_message.'</div>';
    $redirectto = base_url().'manage/regpolicy/show';
    ?>
    <script type="text/javascript">
        function Redirect()
        {
            window.location.href="<?php echo $redirectto;?>";
        }
        setTimeout('Redirect()', 1000);
    </script>
<?php
}
elseif(!empty($form))
{
    echo $form;

}
