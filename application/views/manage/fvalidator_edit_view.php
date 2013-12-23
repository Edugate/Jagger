<div id="pagetitle">
<?php echo lang('title_fedvalidator') ;?>
</div>
<div id="subtitle"><h3>
<?php
echo lang('rr_federation').': '.anchor($federationlink,$federationname);
?></h3>
</div>
<?php
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
echo form_open();
echo '<fieldset><legend>'.lang('general').'</legend><ol>';
echo '<li>';
echo '<label for="vname">'.lang('fvalid_name').'</label>';
echo '<input type="text" id="vname" name="vname" 
                           value="'.set_value('vname',$vname).'" size="40" maxlength="31"/>';
echo '</li>';
echo '<li>';
echo '<label for="vdesc">'.lang('rr_description').'</label>';
echo '<textarea id="vdesc" name="vdesc" cols="65" rows="30">'.set_value('vdesc',$vdesc).'</textarea>' ;
echo '</li>';
echo '<li>';
echo '<label for="vurl">'.lang('fvalid_url').'</label>';
echo '<input type="text" id="vurl" name="vurl"
                           value="'.set_value('vurl',$vurl).'" size="40"/>';
echo '</li>';

echo '<li>';
echo '<label for="vmethod">'.lang('rr_httpmethod').'</label>';
echo '<input type="text" id="vmethod" name="vmethod"
                           value="'.set_value('vmethod',$vmethod).'" maxlength="4" />';
echo '</li>';

echo '<li>';
echo '<label for="vparam">'.lang('fvalid_entparam').'</label>';
echo '<input type="text" id="vparam" name="vparam"
                           value="'.set_value('vparam',$vparam).'" size="40"/>';
echo '</li>';


echo '<li>';
echo '<label for="voptparams">'.lang('fvalid_optargs').'</label>';
echo '<textarea id="voptparams" name="voptparams">'.set_value('voptparams',$voptparams).'</textarea>';
echo '</li>';


echo '<li>';
echo '<label for="vargsep">'.lang('rr_argsep').'</label>';
echo '<input type="text" id="vargsep" name="vargsep"
                           value="'.set_value('vargsep',$vargsep).'" maxlength="10" size="40"/>';
echo '</li>';

echo '<li>';
echo '<label for="vdoctype">'.lang('fvalid_doctype').'</label>';
echo '<input type="text" id="vdoctype" name="vdoctype"
                           value="'.set_value('vdoctype',$vdoctype).'" maxlength="5" size="40"/>';
echo '</li>';

echo '<li>';
echo '<label for="vretelements">'.lang('fvalid_retelemens').'</label>';
echo '<input type="text" id="vretelements" name="vretelements"
                           value="'.set_value('vretelements',$vretelements).'" maxlength="100" size="40"/>';
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

