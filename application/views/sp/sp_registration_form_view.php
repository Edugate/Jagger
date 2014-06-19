<?php
$this->load->helper("cert");
$errors_v = validation_errors('<span>', '</span><br />');
if (!empty($errors_v)) {
    echo '<div class="error">' . $errors_v . '</div>';
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
echo '<div class="small-3 columns">'; 
echo jform_label('Metadata <small>(' . lang('rr_optional') . ')</small>'.showBubbleHelp(lang('rhelp_regspparsemeta')), 'metadatabody');
echo '</div>';
echo '<div class="small-6 large-7 columns">';
echo form_textarea(array(
    'id' => 'metadatabody',
    'name' => 'metadatabody',
    'value' => set_value('metadatabody'),
    'cols' => 65,
    'rows' => 20,
    'style' => 'font-family: monospace; font-size: smaller'
));
echo '</div>';

echo '<div class="small-3 large-2 columns"><button  type="button" name="parsemetadatasp" id="parsemetadatasp" value="parsemetadatasp" class="savebutton">Parse</button>';
echo '</div>';
echo '</div>';
?>
<button type="button" name="next" class="next savebutton button"><?php echo lang('nextstep'); ?></button>
<?php
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
    echo '<div class="small-12 columns"><div class="small-3 columns">';
    echo jform_label(lang('rr_federation') . ' ' . showBubbleHelp(lang('rhelp_onlypublicfeds')) . '', 'federation').'</div>';
    echo '<div class="small-6 large-7 columns end">';
    echo form_dropdown('federation', $federations,set_value('federation'));
    echo '</div>';
    echo '</div>';
}
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">' . jform_label(lang('rr_entityid'), 'entityid');
echo '</div>';
 echo '<div class="small-6 large-7 columns end">';
echo form_input(array(
    'id' => 'entityid',
    'name' => 'entityid',
    'value' => set_value('entityid'),
    'max-length' => 255,
    'class' => 'required'
));
echo '</div>';
echo '</div>';

echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';
echo jform_label(lang('e_orgname'), 'resource');
echo '</div>';
echo '<div class="small-6 large-7 columns end">';
echo form_input(array(
    'id' => 'resource',
    'name' => 'resource',
    'value' => set_value('resource'),
    'max-length' => 255,
    'class' => 'required',
));
echo '</div>';
echo '</div>';

echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';

echo jform_label(lang('e_orgdisplayname'), 'descresource');
echo '</div>';
echo '<div class="small-6 large-7 columns end">';

echo form_input(array(
    'id' => 'descresource',
    'name' => 'descresource',
    'value' => set_value('descresource'),
    'max-length' => 255,
    'class' => 'required',
));
echo '</div>';
echo '</div>';

echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';

echo jform_label(lang('e_orgurl'), 'helpdeskurl');
echo '</div>';
echo '<div class="small-6 large-7 columns end">';

echo form_input(array(
    'id' => 'helpdeskurl',
    'name' => 'helpdeskurl',
    'value' => set_value('helpdeskurl'),
    'max-length' => 255,
    'class' => 'required',
));
echo '</div>';
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



echo '<div class="spregacs">';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">'.jform_label(lang('rr_assertionconsumerservicebind') . showBubbleHelp(lang('rhelp_assertionconsumer')), 'assertionconsumer_binding').'</div>';
$dropdownid = 'id="acs_bind[0]"';
echo '<div class="small-6 large-7 columns end">';
echo form_dropdown('acs_bind[0]', $acs_dropdown, set_value('acs_bind[0]'), $dropdownid);
echo '</div>';
echo '</div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">'.jform_label(lang('rr_acsurl') . showBubbleHelp(lang('rhelp_acsurl')), 'acs_url[0]').'</div>';

$inp = array(
    'id' => 'acs_url[0]',
    'name' => 'acs_url[0]',
    'value' => set_value('acs_url[0]'),
    'class' => 'required',
);
echo '<div class="small-6 large-6 columns">'.form_input($inp).'</div>';
echo '<div class="small-1 large-1 columns text-left end">';
$inp = array(
    'id' => 'acs_order[0]',
    'name' => 'acs_order[0]',
    'length' => 3,
    'value' => set_value('acs_order[0]', 1),
    'class' => 'acsindex required',
);
echo form_input($inp);


echo '</div>';

echo '</div>';
echo '</div>';

foreach($acs as $k => $v)
{
echo '<div class="optspregacs">';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">'.jform_label(lang('rr_assertionconsumerservicebind') . showBubbleHelp(lang('rhelp_assertionconsumer')), 'assertionconsumer_binding').'</div>';
$dropdownid = 'id="acs_bind['.$k.']"';
echo '<div class="small-6 large-7 columns end">'.form_dropdown('acs_bind['.$k.']', $acs_dropdown, set_value('acs_bind['.$k.']'), $v['bind']).'</div>';
echo '</div>';
echo '<div class="small-12 columns">';

echo '<div class="small-3 columns">'.jform_label(lang('rr_acsurl') . showBubbleHelp(lang('rhelp_acsurl')), 'acs_url['.$k.']').'</div>';
$inp = array(
    'id' => 'acs_url['.$k.']',
    'name' => 'acs_url['.$k.']',
    'value' => $v['url'],
);
echo '<div class="small-6 large-6 columns">'.form_input($inp).'</div>';

echo '<div class="small-1 large-1 columns text-left end">';

$inp = array(
    'id' => 'acs_order['.$k.']',
    'name' => 'acs_order['.$k.']',
    'length' => 3,
    'value' => set_value('acs_order['.$k.']',$v['order']),
    'class' => 'acsindex required',
);
echo form_input($inp);
echo '</div>';
echo '</div>';
echo '</div>';

}

echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">'.jform_label('NameId(s) <small>(' . lang('rr_optional') . ')</small>', 'nameids').'</div>';
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
echo '<div class="small-3 columns">'.jform_label(lang('rr_certificatesigning') . ' <small>(' . lang('rr_optional') . ')</small>', 'sign_cert_body').'</div>';
echo '<div class="small-6 large-7 columns end">';
echo form_textarea(array(
    'id' => 'sign_cert_body',
    'name' => 'sign_cert_body',
    'value' => reformatPEM(set_value('sign_cert_body')),
    'cols' => 50,
    'rows' => 20,
    'style' => 'font-family: monospace; font-size: smaller;'
));
echo '</div>';
echo '</div>';
echo '<div class="small-12 columns">';
echo '<div class="small-3 columns">';
echo jform_label(lang('rr_certificateencrypting') . ' <small>(' . lang('rr_optional') . ')</small>', 'encrypt_cert_body');
echo '</div>';
echo '<div class="small-6 large-7 columns end">';
echo form_textarea(array(
    'id' => 'encrypt_cert_body',
    'name' => 'encrypt_cert_body',
    'value' => reformatPEM(set_value('encrypt_cert_body')),
    'cols' => 50,
    'rows' => 20,
    'style' => 'font-family: monospace; font-size: smaller'
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
echo '<div class="small-3 columns">';
echo jform_label(lang('rr_contactname'), 'contact_name');
echo '</div>';
echo '<div class="small-6 large-7 columns end">';
echo form_input(array(
    'id' => 'contact_name',
    'name' => 'contact_name',
    'value' => set_value('contact_name'),
    'class' => 'required',
));
echo '</div>';
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
echo form_input($in3) ;
echo '</div>';
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
echo form_input($in4);
echo '</div>';
echo '</div>';

?>
<button type="button" name="previous" class="previous savebutton"><?php echo lang('prevstep'); ?></button>
<button type="submit" name="submit" value="Submit and wait for approval" class="savebutton saveicon"><?php echo lang('rr_submitwait'); ?></button>

<?php
echo form_fieldset_close();
?>




</form>

