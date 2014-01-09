<div id="pagetitle"><?php echo lang('fedejoinform');?></div>


<?php
echo '<div id="subtitle"><h3><a href="'.base_url().'providers/detail/show/'.$providerid.'">'.$name.'</a></h3><h4>'.$entityid.'</h4></div>';
if(!empty($success_message))
{
    echo '<div class="success">'.$success_message.'</div>';
}
if(!empty($error_message))
{
    echo '<div class="alert">'.$error_message.'</div>';

}
$attrs = array('id'=>'fvform','style'=>'display:none;');
$hidden = array('fedid'=>'','provid'=>''.$providerid.'','fvid'=>'');
echo form_open(base_url().'federations/fvalidator/validate',$attrs,$hidden);
echo '<div id="fvresult" style="display:none;"><div><b>'.lang('fvalidcodereceived').'</b>: <span id="fvreturncode"></span></div><div><p><b>'.lang('fvalidmsgsreceived').'</b>:</p><div id="fvmessages"></div></div></div>';
echo '<p><b>'.lang('validatewithfedvalid').'</b></p>';
echo '<div id="fvalidesc"></div>';
echo '<div class="buttons"><button type="submit" id="fvalidate" name="fvalidate" value="fvalidate" class="editbutton saveicon">'.lang('rr_submit').'</button></div>'; 

?>
</form>
<br />
<?php
if(!empty($form))
{
   echo $form;
}
echo '<div id="retrfvalidatorjson" style="display:none">'.base_url().'federations/fvalidator/detailjson</div>';
?>
