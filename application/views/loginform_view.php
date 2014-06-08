<?php
/**
 * new login form using foundation
 */
$base = base_url();
$this->load->helper('form');
$shib = $this->config->item('Shibboleth');
$ssphp = $this->config->item('simplesamlphp');
$fedenabled = FALSE;
$shib_url = null;

echo '<div id="loginform" class="reveal-modal row small small-11 columns" data-reveal>';
echo '<div id="loginresponse" class="alert-box alert" style="display: none"></div>';

if($fedenabled)
{
   $column_class = 'large-6';
}
else
{
   $column_class = 'large-11';
}
echo '<div class="'.$column_class.' columns">';
echo form_open($base.'authenticate/dologin');
echo '<div class="large-12 columns">';
echo '<div class="small-3 columns"> <label for="username" class="right inline">'.lang('rr_username').'</label> </div> <div class="small-9 columns"> 
             <input type="text" id="username" name="username"></div>';
echo '</div>';
echo '<div class="large-12 columns">';
echo '<div class="small-3 columns"> <label for="password" class="right inline">'.lang('rr_password').'</label> </div> <div class="small-9 columns"> 
             <input type="password" id="password" name="password"></div>';
echo '</div>';
echo '<div class="large-12 columns">';
echo '<div class="small-3 columns"></div>';
echo '<div class="small-9 columns"><button type="submit" class="button small">'.lang('loginsubmit').'</button></div>';
echo '</div>';
echo form_close();
echo '</div>';
if($fedenabled)
{
   
   echo '<div class="'.$column_class.' columns">';
   echo '</div>';

}


echo '<a class="close-reveal-modal">&#215;</a>';
echo '</div>';

