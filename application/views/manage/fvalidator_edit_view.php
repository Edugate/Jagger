<div id="pagetitle">
<?php echo lang('title_fedvalidator') ;?>
</div>
<div id="subtitle"><h3>
<?php
echo lang('rr_federation').': '.anchor($federationlink,$federationname);
?></h3>
</div>
<?php
$errors_v = validation_errors('<p class="error">', '</p>');
if (!empty($errors_v)) {
    echo '<div class="alert">'.$errors_v.'</div>';
}
if(!isset($vname))
{
  $vname = null;
}
if(!isset($vdesc))
{
  $vdesc = null;
}
if(!isset($vurl))
{
  $vurl = null;
}
if(!isset($vmethod))
{
  $vmethod = 'GET';
}
if(!isset($vparam))
{
  $vparam = null;
}
if(!isset($voptparams))
{
  $voptparams=null;
}
if(!isset($vargsep))
{
  $vargsep='&amp';
}
if(!isset($vdoctype))
{
  $vdoctype = 'XML';
}
if(!isset($vretelements))
{
  $vretelements = null;
}
if(!isset($vsuccesscode))
{
  $vsuccesscode =null;
}
if(!isset($vwarningcode))
{
$vwarningcode = null;
}
if(!isset($verrorcode))
{
$verrorcode = null;
}
if(!isset($vcriticalcode))
{
$vcriticalcode =null;
}
if(!isset($vmsgelements))
{
 $vmsgelements = null;
}
if(!isset($venabled))
{
 $venabled = false;
}

echo form_open();
echo '<fieldset><legend>'.lang('general').'</legend><ol>';
echo '<li>';
echo '<label for="vname">'.lang('fvalid_name').'</label>';
echo '<input type="text" id="vname" name="vname" 
                           value="'.set_value('vname',$vname).'" maxlength="31"/>';
echo '</li>';
echo '<li>';
echo '<label for="venabled">'.lang('rr_enabled').'</label>';
?>
<input type="checkbox" name="venabled" id="venabled" value="yes" <?php echo set_checkbox('venabled', 'yes',$venabled); ?> style="margin:10px" />
<?php
echo '</li>';
echo '<li>';
echo '<label for="vdesc">'.lang('rr_description').'</label>';
echo '<textarea id="vdesc" name="vdesc">'.set_value('vdesc',$vdesc).'</textarea>' ;
echo '</li>';
echo '<li>';
echo '<label for="vurl">'.lang('fvalid_url').'</label>';
echo '<input type="text" id="vurl" name="vurl"
                           value="'.set_value('vurl',$vurl).'" />';
echo '</li>';

echo '<li>';
echo '<label for="vmethod">'.lang('rr_httpmethod').'</label>';
echo '<input type="text" id="vmethod" name="vmethod"
                           value="'.set_value('vmethod',$vmethod).'" maxlength="4" />';
echo '</li>';

echo '<li>';
echo '<label for="vparam">'.lang('fvalid_entparam').'</label>';
echo '<input type="text" id="vparam" name="vparam"
                           value="'.set_value('vparam',$vparam).'" />';
echo '</li>';


echo '<li>';
echo '<label for="voptparams">'.lang('fvalid_optargs').'</label>';
echo '<textarea id="voptparams" name="voptparams">'.set_value('voptparams',$voptparams).'</textarea>';
echo '</li>';


echo '<li>';
echo '<label for="vargsep">'.lang('rr_argsep').' (GET)</label>';
echo '<input type="text" id="vargsep" name="vargsep"
                           value="'.set_value('vargsep',$vargsep).'" maxlength="10" />';
echo '</li>';

echo '<li>';
echo '<label for="vdoctype">'.lang('fvalid_doctype').'</label>';
echo '<input type="text" id="vdoctype" name="vdoctype"
                           value="'.set_value('vdoctype',$vdoctype).'" maxlength="5" readonly="readonly"/>';
echo '</li>';


echo '</ol></fieldset>';
echo '<fieldset><legend>Response</legend><ol>';
echo '<li>';
echo '<label for="vretelements">'.lang('fvalid_retelements').'</label>';
echo '<input type="text" id="vretelements" name="vretelements"
                           value="'.set_value('vretelements',$vretelements).'" maxlength="100" />';
echo '</li>';

echo '<li>';
echo '<label for="vsuccesscode">'.lang('fcode_succes').'</label>';
echo '<input type="text" id="vsuccesscode" name="vsuccesscode"
                           value="'.set_value('vsuccesscode',$vsuccesscode).'" maxlength="100" />';
echo '</li>';
echo '<li>';
echo '<label for="vwarningcode">'.lang('fcode_warning').'</label>';
echo '<input type="text" id="vwarningcode" name="vwarningcode"
                           value="'.set_value('vwarningcode',$vwarningcode).'" maxlength="100" />';
echo '</li>';
echo '<li>';
echo '<label for="verrorcode">'.lang('fcode_error').'</label>';
echo '<input type="text" id="verrorcode" name="verrorcode"
                           value="'.set_value('verrorcode',$verrorcode).'" maxlength="100" />';
echo '</li>';
echo '<li>';
echo '<label for="vcriticalcode">'.lang('fcode_crit').'</label>';
echo '<input type="text" id="vcriticalcode" name="vcriticalcode"
                           value="'.set_value('vcriticalcode',$vcriticalcode).'" maxlength="100" />';
echo '</li>';
echo '<li>';
echo '<label for="vmsgelements">'.lang('fvalid_msgelements').'</label>';
echo '<input type="text" id="vmsgelements" name="vmsgelements"
                           value="'.set_value('vmsgelements',$vmsgelements).'" maxlength="100" />';
echo '</li>';


echo '<ol></fieldset>';

echo '<div class="buttons">';
if(empty($newfvalidator))
{
   echo '<button type="submit" id="rmfedvalidator" name="formsubmit" value="remove" class="resetbutton deleteicon">'.lang('rr_remove').'</button>';
}
echo '<button type="submit" name="formsubmit" value="update" class="savebutton saveicon">'.lang('rr_save').'</button></div>';
echo form_close();

echo confirmDialog(''.lang('title_confirm').'', ''.sprintf(lang('douwanttoremove'),lang('fedvalidator')).'', ''.lang('rr_yes').'', ''.lang('rr_no').'');
echo form_close();

