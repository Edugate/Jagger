<?php
$this->load->helper("cert");
?>
<?php
$errors_v = validation_errors('<span>', '</span><br />');
if (!empty($errors_v)) {
    echo '<div class="error">' . $errors_v . '</div>';
}
if(!empty($additional_error))
{
    echo '<div class="error">'. $additional_error .'</div>';
}

$form_attributes = array('id' => 'multistepform', 'class' => 'register');
$action = current_url();
echo form_open($action, $form_attributes);
?>
<ul id="progressbar" class="foursteps">
    <?php
    echo '<li class="active">' . lang('rr_formstep') . '1</li><li>' . lang('rr_formstep') . '2</li><li>' . lang('rr_formstep') . '3</li><li>' . lang('rr_formstep') . '4</li>';
    ?>
</ul>

<!-- step1 -->
<?php
echo form_fieldset('Step1');
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns text-right">'.jform_label('Metadata <small>(' . lang('rr_optional') . ')</small>'.showBubbleHelp(lang('rhelp_regspparsemeta')), 'metadatabody').'</div>';
echo '<div class="small-6 large-7 columns">'.form_textarea(array(
    'id' => 'metadatabody',
    'name' => 'metadatabody',
    'value' => set_value('metadatabody'),
    'cols' => 65,
    'rows' => 20,
    'style' => 'font-family: monospace; font-size: smaller'
)).'</div>';
echo '<div class="small-3 large-2 columns"><button  type="button" name="parsemetadataidp" id="parsemetadataidp" value="parsemetadataidp" class="savebutton button tiny">'.lang('btnparsemeta').'</button></div>';
echo '</div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">'.jform_label(lang('advancedmode'),'advanced').'</div>';
echo '<div class="small-6 large-7 end columns text-left"><input type="checkbox" name="advanced" id="advanced" value="advanced"/></div>';
echo '</div>'; 
echo '<button type="button" name="next" class="simplemode next savebutton button">'. lang('nextstep').'</button>';
echo '<button type="submit" name="next" class="advancedmode button" value="'.base_url().'providers/idp_registration/advanced">'.lang('btngoadvancedmode').'</button>';
echo form_fieldset_close();
?>
<!-- step2 -->
<?php
echo form_fieldset('General');
/**
 * federation select
 */
if(!empty($federations) && is_array($federations))
{
   echo '<div class="small-12 columns">';
   echo '<div class="small-3 columns">'.jform_label(lang('rr_federation') . ' ' . showBubbleHelp(lang('rhelp_onlypublicfeds')) . '', 'federation').'</div>';
   echo '<div class="small-6 large-7 columns end">'.form_dropdown('federation', $federations,set_value('federation')).'</div>';
   echo '</div>';
}

echo '<div class="small-12 columns"><div class="small-3 columns">' . jform_label(lang('rr_entityid'), 'entityid').'</div>';
echo '<div class="small-6 large-7 columns end">'.form_input(array(
    'id' => 'entityid',
    'name' => 'entityid',
    'value' => set_value('entityid'),
    'max-length' => 255,
    'class' => 'required'
)).'</div>';
echo '</div>';
echo '<div class="small-12 columns"><div class="small-3 columns">' . jform_label(lang('e_orgname'), 'homeorg').'</div>';
echo '<div class="small-6 large-7 columns end">'.form_input(array(
    'id' => 'homeorg',
    'name' => 'homeorg',
    'value' => set_value('homeorg'),
    'max-length' => 255,
    'class' => 'required',
)).'</div>';
echo '</div>';

echo '<div class="small-12 columns"><div class="small-3 columns">' . jform_label(lang('e_orgdisplayname'), 'deschomeorg').'</div>';
echo '<div class="small-6 large-7 columns end">'.form_input(array(
    'id' => 'deschomeorg',
    'name' => 'deschomeorg',
    'value' => set_value('deschomeorg'),
    'max-length' => 255,
    'class' => 'required',
)).'</div>';
echo '</div>';
echo '<div class="small-12 columns"><div class="small-3 columns">' . jform_label(lang('e_orgurl').'<br /><small><i>('.lang('rr_helpdeskurl').')</i></small>', 'helpdeskurl').'</div>';
echo '<div class="small-6 large-7 columns end">'.form_input(array(
    'id' => 'helpdeskurl',
    'name' => 'helpdeskurl',
    'value' => set_value('helpdeskurl'),
    'max-length' => 255,
    'class' => 'required',
)).'</div>';
echo '</div>';

?>
<button type="button" name="previous" class="previous savebutton"><?php echo lang('prevstep'); ?></button>
<button type="button" name="next" class="next savebutton"><?php echo lang('nextstep'); ?></button>

<?php
echo form_fieldset_close();
?>
<!-- step3 -->

<?php


echo form_fieldset(lang('rr_technicalinformation'));
echo '<div class="small-12 columns">';

echo '<div class="small-3 columns">';
echo jform_label(lang('rr_scope').'<br /><small>IDPSSODescriptor</small>','idpssoscope');
echo '</div>';
echo '<div class="small-6 large-7 columns end">';
echo form_input(
    array(
     'id'=>'idpssoscope',
     'name' => 'idpssoscope',
     'value'=>set_value('idpssoscope'),
     'class'=>'required' 
   )
);
echo '</div>';

echo '</div>';


foreach($idpssobindprotocols as $k=> $s)
{
   echo '<div class="ssourls small-12 columns">';
   echo '<div class="small-3 columns">';
   echo jform_label(lang('rr_singlesignon_fieldset').'<br /><small>'.$s.'</small>','sso['.$k.']');
   echo '</div>';
   echo '<div class="small-6 large-7 columns end">';
   echo form_input(array(
        'id'=>'sso['.$k.']',
        'name'=>'sso['.$k.']',
        'value'=>set_value('sso['.$k.']'),
      ));
   echo '</div>';
   echo '</div>';
}




echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">'.jform_label('NameId(s)<br/> <small>IDPSSODescriptor (' . lang('rr_optional') . ')</small>', 'nameids').'</div>';
echo '<div class="small-6 large-7 columns end">';
echo form_textarea(array(
    'id' => 'nameids',
    'name' => 'nameids',
    'value' => set_value('nameids'),
    'cols' => 50,
    'rows' => 2,
    'style' => 'font-family: monospace; font-size: smaller;'
));
echo '</div>';


echo '</div>';


echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">'.jform_label(lang('rr_certificatesigning') .'<br /><small>IDPSSODescriptor</small>', 'sign_cert_body').'</div>';
echo '<div class="small-6 large-7 columns end">'.form_textarea(array(
    'id' => 'sign_cert_body',
    'name' => 'sign_cert_body',
    'value' => reformatPEM(set_value('sign_cert_body')),
    'cols' => 50,
    'rows' => 20,
    'style' => 'font-family: monospace; font-size: smaller;',
    'class' => 'required'
));
echo '</div>';
echo '</div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';
echo jform_label(lang('rr_certificateencrypting') .'<br /><small>IDPSSODescriptor</small>' , 'encrypt_cert_body');
echo '</div>';
echo '<div class="small-6 large-7 columns end">';
echo form_textarea(array(
    'id' => 'encrypt_cert_body',
    'name' => 'encrypt_cert_body',
    'value' => reformatPEM(set_value('encrypt_cert_body')),
    'cols' => 50,
    'rows' => 20,
    'style' => 'font-family: monospace; font-size: smaller',
    'class'=>'required'
));
echo '</div>';
echo '</div>';
?>


<button type="button" name="previous" class="previous savebutton"><?php echo lang('prevstep'); ?></button>
<button type="button" name="next" class="next savebutton"><?php echo lang('nextstep'); ?></button>

<?php
echo form_fieldset_close();
?>
<!-- step4 -->
<?php
echo form_fieldset(lang('rr_primarycontact'));
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">' . jform_label(lang('rr_contactfirstname'), 'contactfname') . '</div>';
echo '<div class="small-6 large-7 columns end">' . form_input(array('id' => 'contactfname', 'name' => 'contactfname', 'value' => set_value('contactfname'))) . '</div>';
echo '</div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">' . jform_label(lang('rr_contactlastname'), 'contactlname') . '</div>';
echo '<div class="small-6 large-7 columns end">' . form_input(array('id' => 'contactlname', 'name' => 'contactlname', 'value' => set_value('contactlname'))) . '</div>';
echo '</div>';
$in3 = array('id' => 'contact_mail',
    'name' => 'contact_mail',
    'value' => set_value('contact_mail'),
    'class' => 'required',
);
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';

echo jform_label(lang('rr_contactemail'), 'contact_mail');
echo '</div>';
echo '<div class="small-6 large-7 columns end">';

echo form_input($in3) . '</div>';
echo '</div>';

$in4 = array(
    'id' => 'contact_phone',
    'name' => 'contact_phone',
    'value' => set_value('contact_phone'),
);
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';

echo jform_label(lang('rr_contactphone') . ' <small>(' . lang('rr_optional') . ')</small>', 'contact_phone');
echo '</div>';
echo '<div class="small-6 large-7 columns end">';

echo form_input($in4) . '</div>';
echo '</div>';
?>
<button type="button" name="previous" class="previous savebutton"><?php echo lang('prevstep'); ?></button>
<button type="submit" name="submit" value="Submit and wait for approval" class="savebutton saveicon"><?php echo lang('rr_submitwait'); ?></button>

<?php
echo form_fieldset_close();
?>




</form>

