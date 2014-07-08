<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if(!empty($success_message))
{
    echo '<div  data-alert class="alert-box success">'.$success_message.'</div>';
}
if(!empty($error_message))
{
    echo '<div  data-alert class="alert-box alert">'.$error_message.'</div>';

}

echo '<div class="button-bar"><ul class="button-group janusz"></ul></div>';
$attrs = array('id'=>'fvform','style'=>'display:none;','class'=>'columns alert-box secondary');
$hidden = array('fedid'=>'','provid'=>''.$providerid.'','fvid'=>'');
echo '<div class="small-12 medium-10  columns">'.form_open(base_url().'federations/fvalidator/validate',$attrs,$hidden);
echo '<div id="fvresult" style="display:none;" data-alert class="alert-box info"><div><b>'.lang('fvalidcodereceived').'</b>: <span id="fvreturncode"></span></div><div><p><b>'.lang('fvalidmsgsreceived').'</b>:</p><div id="fvmessages"></div></div></div>';
echo '<p><b>'.lang('validatewithfedvalid').'</b></p>';
echo '<div id="fvalidesc"></div>';
echo '<div class="buttons"><button type="submit" id="fvalidate" name="fvalidate" value="fvalidate" class="editbutton saveicon">'.lang('rr_submit').'</button></div>'; 

?>
</form></div>
<br />
<?php
if(!empty($form))
{
   echo '<div class="small-12 columns">'.$form.'</div>';
}
echo '<div id="retrfvalidatorjson" style="display:none">'.base_url().'federations/fvalidator/detailjson</div>';
?>
