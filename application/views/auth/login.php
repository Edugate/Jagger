<?php
$base = base_url();
$this->load->helper('form');
$localloginbtn = $this->config->item('localloginbtn');
$shib = $this->config->item('Shibboleth');
$fedenabled = FALSE;
$shib_url = null;

if (isset($shib['enabled']) && $shib['enabled'] === TRUE)
{
    if (isset($shib['loginapp_uri']))
    {
        $shib_url = base_url() . $shib['loginapp_uri'];
        $fedenabled = TRUE;
    }
    else
    {
        log_message('error', 'Federation login enabled but fedurl not found');
    }
}

if (empty($localloginbtn))
{
    $localloginbtn = lang('rr_local_authn');
}
$fedloginbtn = $this->config->item('fedloginbtn');
if (empty($fedloginbtn))
{
    $fedloginbtn = lang('federated_access');
}
$attributes = array('id' => 'login', 'style' => 'display:block');


if (!empty($showloginform))
{
    echo '<div id="loginform2">';
   
}
else
{
    
    echo '<div id="login_form" style="display:none">';
}
?>
<div id="login_form" >

    <div id="status" style="text-align:left">
       
        <div id="login_response"><!-- spanner --></div>

        <?php
//echo form_open("auth/login", $attributes); 
        
        if ($fedenabled === TRUE)
        {
            echo '<div class="column one">';
        }
        echo form_open("javascript:alert('success!');", $attributes);
        ?>

        <input type="hidden" name="action" value="user_login">
        <input type="hidden" name="module" value="login">
        <input type="hidden" name="baseurl" value="<?php echo base_url(); ?>">

        <fieldset>
            <legend><?php echo 'Log in with local account' ?></legend>
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
                <button type="submit" name="Login" id="submit" value="Login" class="loginbtn">Submit</button>
            </div>
        </fieldset>
 <div id="ajax_loading">
            <img align="absmiddle" src="<?php echo base_url() . 'images/spinner.gif'; ?>">&nbsp;Processing...
        </div>
        <?php echo form_close(); ?>
<?php
if ($fedenabled === TRUE)
{
    
       
        
    echo '</div><div class="column two">';
    ?>
        <form id="federated" method="get">
            <fieldset>
            <legend><?php echo $fedloginbtn; ?></legend>
            </fieldset>
            <div class="buttons">
        <?php
    echo anchor($shib_url, '<button type="button" name="faderated" value="faderated" class="loginbtn" onclick="window.open(\'' . $shib_url . '\',\'_self\')">' . $fedloginbtn . '</button>');
    ?>
            </div>
        </form>
            <?php
    echo '</div>';
    
}
?> 

       



    </div>    
</div>
<?php
if(!empty($showloginform))
{
    echo '<div style="clear: both;"></div>';
    echo '</div>';
}
?>
</div>
