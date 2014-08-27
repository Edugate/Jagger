<?php
$errors_v = validation_errors('<div class="error">', '</div>');
if (!empty($errors_v)) {
    echo '<div data-alert class="alert-box alert">'.$errors_v.'</div>';
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
if(!isset($vmandatory))
{
 $vmandatory = false;
}

echo form_open();
echo '<fieldset><legend>'.lang('general').'</legend>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';
echo '<label for="vname" class="right inline">'.lang('fvalid_name').'</label>';
echo '</div><div class="small-6 large-7 columns end">';
echo '<input type="text" id="vname" name="vname" 
                           value="'.set_value('vname',$vname).'" maxlength="31"/>';
echo '</div></div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';
echo '<label for="venabled" class="right inline">'.lang('rr_enabled').'</label>';
echo '</div><div class="small-6 large-7 columns end">';

?>
<input type="checkbox" name="venabled" id="venabled" value="yes" <?php echo set_checkbox('venabled', 'yes',$venabled); ?> style="margin:10px" />
<?php
echo '</div></div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';
echo '<label for="vmandatory" class="right inline">'.lang('rr_mandatory').'</label>';
echo '</div><div class="small-6 large-7 columns end">';
?>
<input type="checkbox" name="vmandatory" id="vmandatory" value="yes" <?php echo set_checkbox('vmandatory', 'yes', $vmandatory); ?> style="margin:10px" />
<?php
echo '</div></div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';

echo '<label for="vdesc" class="right inline">'.lang('rr_description').'</label>';
echo '</div><div class="small-6 large-7 columns end">';
echo '<textarea id="vdesc" name="vdesc">'.set_value('vdesc',$vdesc).'</textarea>' ;
echo '</div></div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';

echo '<label for="vurl" class="right inline">'.lang('fvalid_url').'</label>';
echo '</div><div class="small-6 large-7 columns end">';
echo '<input type="text" id="vurl" name="vurl"
                           value="'.set_value('vurl',$vurl).'" />';
echo '</div></div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';
echo '<label for="vmethod" class="right inline">'.lang('rr_httpmethod').'</label>';
echo '</div><div class="small-6 large-7 columns end">';

echo '<input type="text" id="vmethod" name="vmethod"
                           value="'.set_value('vmethod',$vmethod).'" maxlength="4" />';
echo '</div></div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';

echo '<label for="vparam" class="right inline">'.lang('fvalid_entparam').'</label>';
echo '</div><div class="small-6 large-7 columns end">';

echo '<input type="text" id="vparam" name="vparam"
                           value="'.set_value('vparam',$vparam).'" />';

echo '</div></div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';


echo '<label for="voptparams" class="right inline">'.lang('fvalid_optargs').' '.showBubbleHelp(sprintf(lang('rhelp_multargvalinputseparator'),'&#36;&#36;','&#36;:&#36;','arg1:val1  arg2:val2 - &gt; arg1&#36;:&#36;val1&#36;&#36;arg2&#36;:&#36;val2')).'</label>';
echo '</div><div class="small-6 large-7 columns end">';
echo '<textarea id="voptparams" name="voptparams">'.set_value('voptparams',$voptparams).'</textarea>';

echo '</div></div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';

echo '<label for="vargsep" class="right inline">'.lang('rr_argsep').' (GET)</label>';
echo '</div><div class="small-6 large-7 columns end">';

echo '<input type="text" id="vargsep" name="vargsep"
                           value="'.set_value('vargsep',$vargsep).'" maxlength="10" />';
echo '</div></div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';

echo '<label for="vdoctype" class="right inline">'.lang('fvalid_doctype').'</label>';
echo '</div><div class="small-6 large-7 columns end">';

echo '<input type="text" id="vdoctype" name="vdoctype"
                           value="'.set_value('vdoctype',$vdoctype).'" maxlength="5" readonly="readonly"/>';
echo '</div></div>';


echo '</fieldset>';
echo '<fieldset><legend>Response</legend>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';

echo '<label for="vretelements" class="right inline">'.lang('fvalid_retelements').' '.showBubbleHelp(sprintf(lang('rhelp_multiplvalssep'),'whitespace')).'</label>';
echo '</div><div class="small-6 large-7 columns end">';
echo '<input type="text" id="vretelements" name="vretelements"
                           value="'.set_value('vretelements',$vretelements).'" maxlength="100" />';

echo '</div></div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';

echo '<label for="vsuccesscode" class="right inline">'.lang('fcode_succes').' '.showBubbleHelp(sprintf(lang('rhelp_multiplvalssep'),'&#36;&#36;')).'</label>';
echo '</div><div class="small-6 large-7 columns end">';
echo '<input type="text" id="vsuccesscode" name="vsuccesscode"
                           value="'.set_value('vsuccesscode',$vsuccesscode).'" maxlength="100" />';
echo '</div></div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';

echo '<label for="vwarningcode" class="right inline">'.lang('fcode_warning').' '.showBubbleHelp(sprintf(lang('rhelp_multiplvalssep'),'&#36;&#36;')).'</label>';
echo '</div><div class="small-6 large-7 columns end">';
echo '<input type="text" id="vwarningcode" name="vwarningcode"
                           value="'.set_value('vwarningcode',$vwarningcode).'" maxlength="100" />';
echo '</div></div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';

echo '<label for="verrorcode" class="right inline">'.lang('fcode_error').' '.showBubbleHelp(sprintf(lang('rhelp_multiplvalssep'),'&#36;&#36;')).'</label>';
echo '</div><div class="small-6 large-7 columns end">';
echo '<input type="text" id="verrorcode" name="verrorcode"
                           value="'.set_value('verrorcode',$verrorcode).'" maxlength="100" />';
echo '</div></div>';

echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';

echo '<label for="vcriticalcode" class="right inline">'.lang('fcode_crit').' '.showBubbleHelp(sprintf(lang('rhelp_multiplvalssep'),'&#36;&#36;')).'</label>';
echo '</div><div class="small-6 large-7 columns end">';
echo '<input type="text" id="vcriticalcode" name="vcriticalcode"
                           value="'.set_value('vcriticalcode',$vcriticalcode).'" maxlength="100" />';
echo '</div></div>';

echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';

echo '<label for="vmsgelements" class="right inline">'.lang('fvalid_msgelements').' '.showBubbleHelp(sprintf(lang('rhelp_multiplvalssep'),'whitespace')).'</label>';
echo '</div><div class="small-6 large-7 columns end">';
echo '<input type="text" id="vmsgelements" name="vmsgelements"
                           value="'.set_value('vmsgelements',$vmsgelements).'" maxlength="100" />';

echo '</div></div>';

echo '</fieldset>';

echo '<div class="buttons small-12 medium-10 large-10 end columns text-right">';
if(empty($newfvalidator))
{
   echo '<button type="submit" id="rmfedvalidator" name="formsubmit" value="remove" class="resetbutton deleteicon">'.lang('rr_remove').'</button>';
}
echo '<button type="submit" name="formsubmit" value="update" class="savebutton saveicon">'.lang('rr_save').'</button></div>';
echo form_close();

echo confirmDialog(''.lang('title_confirm').'', ''.sprintf(lang('douwanttoremove'),lang('fedvalidator')).'', ''.lang('rr_yes').'', ''.lang('rr_no').'');
echo form_close();

