<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

if(!empty($success_message))
{
    echo '<div  data-alert class="alert-box success">'.$success_message.'</div>';
}
if(!empty($error_message))
{
    echo '<div  data-alert class="alert-box alert">'.$error_message.'</div>';

}
echo '<div data-alert class="alert-box warning validaronotice hidden">'.lang('fvalidatorjoinfed').'</div>';
echo '<div class="button-bar"><ul class="button-group validatorbuttons"></ul></div>';

$attrs = array('id'=>'fvform', 'class'=>'columns alert-box secondary hidden');
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
if(!empty($showform))
{
    echo '<div class="small-12 column">';
    $buttons = '<button type="submit" name="modify" value="submit" class="savebutton saveicon">' . lang('rr_apply') . '</button>';
    $form = form_open(null, array('id' => 'joinfed'));
    $form .= '<div class="small-12 columns"><div class="small-3 columns">';
    $form .= '<label for="fedid" class="right inline">' . lang('rr_selectfedtojoin') . '</label></div>';
    $form .= '<div class="small-6 large-7 columns end">' . form_dropdown('fedid', $feds_dropdown, '0', 'id="fedid" class="select2"') . '</div>';
    $form .= '</div><div class="small-12 columns">';
    $form .= '<div class="small-3 columns"><label for="formmessage" class="inline right">' . lang('rr_message') . '</label></div>';
    $form .= '<div class="small-6 large-7 columns end">' . form_textarea('formmessage', set_value('formmessage')) . '</div>';
    $form .= '</div>';
    $form .= '<div class="buttons small-12 medium-10 large-10 column end text-right  ">'.$buttons.'</div>';
    $form .= form_close();
    echo $form;
    echo '</div>';
}
echo '<div id="retrfvalidatorjson" style="display:none">'.base_url().'federations/fvalidator/detailjson</div>';
