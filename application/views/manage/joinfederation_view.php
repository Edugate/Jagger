<div id="pagetitle"><?php echo lang('fedejoinform');?></div>


<?php
if(!empty($subtitle))
{
   echo '<div id="subtitle"><h3><a href="'.base_url().'providers/detail/show/'.$providerid.'">'.$name.'</a></h3><h4>'.$entityid.'</h4></div>';
}
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
echo '<p><b>'.lang('validatewithfedvalid').'</b></p>';
echo '<div id="fvalidesc"></div>';
echo '<div class="buttons"><button type="submit" id="fvalidate" name="fvalidate" value="fvalidate" class="editbutton saveicon">'.lang('rr_submit').'</button></div>'; 

?>
</form>
<br />
<div id="fvresult" style="display:none;"></div>
<?php
if(!empty($form))
{
   echo $form;
}
echo '<div id="retrfvalidatorjson" style="display:none">'.base_url().'federations/fvalidator/detailjson</div>';
?>
