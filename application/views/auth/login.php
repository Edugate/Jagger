<?php
$base = base_url();
$this->load->helper('form');
$localloginbtn = $this->config->item('localloginbtn');
$shib = $this->config->item('Shibboleth');
        if (isset($shib['enabled']) && $shib['enabled'] === TRUE)
        {
            $shib_url = base_url() . $shib['loginapp_uri'];
        }
        else
        {
            $shib_url = null;
        }

if(empty($localloginbtn))
{
    $localloginbtn = lang('rr_local_authn');
}
$fedloginbtn = $this->config->item('fedloginbtn');
if(empty($fedloginbtn))
{
  $fedloginbtn = lang('federated_access');
}
$attributes = array('id' => 'login' );
?>


<?php
if(!empty($showloginform))
{
    echo '<div id="login_form">';
}
else
{
    echo '<div id="login_form" style="display:none">';
}
?>
<div id="login_form" >
<div id="status" align="left">
<div id="login_response"><!-- spanner --></div>

<?php 

//echo form_open("auth/login", $attributes); 
echo form_open("javascript:alert('success!');", $attributes); 

?>
<input type="hidden" name="action" value="user_login">
<input type="hidden" name="module" value="login">
<input type="hidden" name="baseurl" value="<?php echo base_url();?>">

<fieldset>
    <legend><?php echo lang('login_form') ?></legend>
    <?php
    $v_errors = validation_errors('<div>', '</div>');
    if (!empty($v_errors))
    {
        echo '<div class="error">';
        echo $v_errors;
        echo "</div>";
    }
    elseif (!empty($message))
    {
        echo '<div class="error">';
        echo $message;
        echo "</div>";
    }
    ?>
    <ol>
        <li>
            <?php
            echo form_label(lang('rr_username'), 'username');
            echo form_input('username');
            ?>
        </li>
        <li>

            <label for="password"><?php echo lang('rr_password'); ?></label>
            <?php
            echo form_password('password');
            ?>
        </li>
    </ol>
    <div class="buttons">

        <?php
        if (!empty($shib_url))
        {
            echo anchor($shib_url, '<button type="button" name="faderated" value="faderated" class="btn" onclick="window.open(\'' . $shib_url . '\',\'_self\')">' . $fedloginbtn . '</button>');
        }
        ?> 
        <button type="submit" name="Login" id="submit" value="Login" class="btn"><?php echo $localloginbtn; ?></button>
    </div>
</fieldset>
<div id="ajax_loading">
         <img align="absmiddle" src="<?php echo base_url().'images/spinner.gif'; ?>">&nbsp;Processing...
        </div>

<?php echo form_close(); ?>
</div>
</div>    
</div>
