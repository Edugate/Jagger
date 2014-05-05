<?php
$this->load->helper("cert");
?>
<div id="subtitle"><h3><?php echo lang('rr_sp_register_title'); ?></h3></div>
<?php
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
echo '<ol>';
echo '<li>';
echo form_label('Metadata <small>(' . lang('rr_optional') . ')</small>'.showBubbleHelp(lang('rhelp_regspparsemeta')), 'metadatabody');
echo form_textarea(array(
    'id' => 'metadatabody',
    'name' => 'metadatabody',
    'value' => set_value('metadatabody'),
    'cols' => 65,
    'rows' => 20,
    'style' => 'font-family: monospace; font-size: smaller'
));
echo '<div class="buttons"><button  type="button" name="parsemetadatasp" id="parsemetadatasp" value="parsemetadatasp" class="savebutton">Parse</button>';
echo '</li>';
echo '</ol>';
?>
<button type="button" name="next" class="next savebutton"><?php echo lang('nextstep'); ?></button>
<?php
echo form_fieldset_close();
?>
<!-- step2 -->
<?php
echo form_fieldset('General');
echo '<ol>';
echo '</li>';
/**
 * federation select
 */
if(!empty($federations) && is_array($federations))
{
    echo '<li>';
    echo form_label(lang('rr_federation') . ' ' . showBubbleHelp(lang('rhelp_onlypublicfeds')) . '', 'federation');
    echo form_dropdown('federation', $federations,set_value('federation'));
    echo '</li>';
}
echo '<li>' . form_label(lang('rr_entityid'), 'entityid');
echo form_input(array(
    'id' => 'entityid',
    'name' => 'entityid',
    'value' => set_value('entityid'),
    'max-length' => 255,
    'class' => 'required'
));
echo '</li>';
echo '<li>' . form_label(lang('rr_resource'), 'resource');
echo form_input(array(
    'id' => 'resource',
    'name' => 'resource',
    'value' => set_value('resource'),
    'max-length' => 255,
    'class' => 'required',
));
echo '</li>';

echo '<li>' . form_label(lang('rr_descriptivename'), 'descresource');
echo form_input(array(
    'id' => 'descresource',
    'name' => 'descresource',
    'value' => set_value('descresource'),
    'max-length' => 255,
    'class' => 'required',
));
echo '</li>';
echo '<li>' . form_label(lang('rr_helpdeskurl'), 'helpdeskurl');
echo form_input(array(
    'id' => 'helpdeskurl',
    'name' => 'helpdeskurl',
    'value' => set_value('helpdeskurl'),
    'max-length' => 255,
    'class' => 'required',
));
echo '</li>';

echo '</ol>';
?>
<button type="button" name="previous" class="previous savebutton"><?php echo lang('prevstep'); ?></button>
<button type="button" name="next" class="next savebutton"><?php echo lang('nextstep'); ?></button>

<?php
echo form_fieldset_close();
?>
<!-- step3 -->

<?php
echo form_fieldset(lang('rr_technicalinformation'));
echo '<ol>';



echo '<li class="spregacs">';
echo form_label(lang('rr_assertionconsumerservicebind') . showBubbleHelp(lang('rhelp_assertionconsumer')), 'assertionconsumer_binding');
$dropdownid = 'id="acs_bind[0]"';
echo form_dropdown('acs_bind[0]', $acs_dropdown, set_value('acs_bind[0]'), $dropdownid);
echo form_label(lang('rr_acsurl') . showBubbleHelp(lang('rhelp_acsurl')), 'acs_url[0]');
$inp = array(
    'id' => 'acs_url[0]',
    'name' => 'acs_url[0]',
    'value' => set_value('acs_url[0]'),
    'class' => 'required',
    'style'=> 'width: 400px; max-width: 400px;',
);
echo '<br />'.form_input($inp);
echo "&nbsp;&nbsp;index";
$inp = array(
    'id' => 'acs_order[0]',
    'name' => 'acs_order[0]',
    'length' => 3,
    'value' => set_value('acs_order[0]', 1),
    'class' => 'acsindex required',
    'style' => 'max-width: 15px; min-width: 10px',
);
echo form_input($inp);
echo '</li>';

foreach($acs as $k => $v)
{
echo '<li class="optspregacs">';
echo form_label(lang('rr_assertionconsumerservicebind') . showBubbleHelp(lang('rhelp_assertionconsumer')), 'assertionconsumer_binding');
$dropdownid = 'id="acs_bind['.$k.']"';
echo form_dropdown('acs_bind['.$k.']', $acs_dropdown, set_value('acs_bind['.$k.']'), $v['bind']);
echo form_label(lang('rr_acsurl') . showBubbleHelp(lang('rhelp_acsurl')), 'acs_url['.$k.']');
$inp = array(
    'id' => 'acs_url['.$k.']',
    'name' => 'acs_url['.$k.']',
    'value' => $v['url'],
    'style'=> 'width: 400px; max-width: 400px;',
);
echo '<br />'.form_input($inp);
echo "&nbsp;&nbsp;index";
$inp = array(
    'id' => 'acs_order['.$k.']',
    'name' => 'acs_order['.$k.']',
    'length' => 3,
    'value' => set_value('acs_order['.$k.']',$v['order']),
    'class' => 'acsindex required',
    'style' => 'max-width: 15px; min-width: 10px',
);
echo form_input($inp);
echo '</li>';

}

echo '<li>';
echo form_label('NameId(s) <small>(' . lang('rr_optional') . ')</small>', 'nameids');
echo form_textarea(array(
    'id' => 'nameids',
    'name' => 'nameids',
    'value' => set_value('nameids'),
    'cols' => 50,
    'rows' => 2,
    'style' => 'font-family: monospace; font-size: smaller;'
));


echo '</li>';


echo '<li>';
echo form_label(lang('rr_certificatesigning') . ' <small>(' . lang('rr_optional') . ')</small>', 'sign_cert_body');
echo form_textarea(array(
    'id' => 'sign_cert_body',
    'name' => 'sign_cert_body',
    'value' => reformatPEM(set_value('sign_cert_body')),
    'cols' => 50,
    'rows' => 20,
    'style' => 'font-family: monospace; font-size: smaller;'
));
echo '</li>';
echo '<li>';
echo form_label(lang('rr_certificateencrypting') . ' <small>(' . lang('rr_optional') . ')</small>', 'encrypt_cert_body');
echo form_textarea(array(
    'id' => 'encrypt_cert_body',
    'name' => 'encrypt_cert_body',
    'value' => reformatPEM(set_value('encrypt_cert_body')),
    'cols' => 50,
    'rows' => 20,
    'style' => 'font-family: monospace; font-size: smaller'
));
echo '</li>';
echo '</ol>';
?>
<button type="button" name="previous" class="previous savebutton"><?php echo lang('prevstep'); ?></button>
<button type="button" name="next" class="next savebutton"><?php echo lang('nextstep'); ?></button>

<?php
echo form_fieldset_close();
?>
<!-- step4 -->
<?php
echo form_fieldset(lang('rr_primarycontact'));
echo '<ol><li>';
echo form_label(lang('rr_contactname'), 'contact_name');
echo form_input(array(
    'id' => 'contact_name',
    'name' => 'contact_name',
    'value' => set_value('contact_name'),
    'class' => 'required',
));
echo '</li><li>';
$in3 = array('id' => 'contact_mail',
    'name' => 'contact_mail',
    'value' => set_value('contact_mail'),
    'class' => 'required',
);
echo form_label(lang('rr_contactemail'), 'contact_mail');
echo form_input($in3) . '</li>';

echo '<li>';
$in4 = array(
    'id' => 'contact_phone',
    'name' => 'contact_phone',
    'value' => set_value('contact_phone'),
);

echo form_label(lang('rr_contactphone') . ' <small>(' . lang('rr_optional') . ')</small>', 'contact_phone');
echo form_input($in4) . '</li>';
echo '</ol>';
?>
<button type="button" name="previous" class="previous savebutton"><?php echo lang('prevstep'); ?></button>
<button type="submit" name="submit" value="Submit and wait for approval" class="savebutton saveicon"><?php echo lang('rr_submitwait'); ?></button>

<?php
echo form_fieldset_close();
?>




</form>

