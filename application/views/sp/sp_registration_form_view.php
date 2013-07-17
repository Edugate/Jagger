<?php
$this->load->helper("cert");
?>
<div id="subtitle"><h3><?php echo lang('rr_sp_register_title');?></h3></div>
<?php
$required = "required=\"required\"";
$errors_v = validation_errors('<span class="span-12">', '</span><br />');
if (!empty($errors_v)) {
    echo '<div class="error">';
    echo $errors_v;
    echo "</div>";
}

echo "<div =\"step1\">";
$form_attributes = array('id' => 'formver2', 'class' => 'register');
$action = base_url() . "providers/sp_registration/submit";
echo form_open($action, $form_attributes);
echo form_fieldset(lang('rr_generalinformation'));
echo "<ol>";
/**
 * resource name input
 */
echo "<li>";
echo lang('rr_resource', 'resource');
$in1 = array(
    'id' => 'resource',
    'name' => 'resource',
    'value' => set_value('resource'),
    'max-length' => 255,
);
echo form_input($in1);
echo "</li>";

/**
 * entityID input
 */
echo "<li>";
echo form_label(lang('rr_entityid').showBubbleHelp(lang('rhelp_entityid')), 'entityid');
$in2 = array(
    'id' => 'entityid',
    'name' => 'entityid',
    'value' => set_value('entityid'),
);
echo form_input($in2);
echo "</li>";
/**
 * federation select
 */
echo "<li>";
echo form_label(lang('rr_federation'), 'federation');
echo form_dropdown('federation', $federations);
echo "</li>";

/**
 * helpdesk url
 */
echo "<li>";
echo form_label(lang('rr_helpdeskurl').showBubbleHelp(lang('rhelp_helpdeskurl')), 'helpdesk_url');
$inp = array(
    'id' => 'helpdesk_url',
    'name' => 'helpdesk_url',
    'value' => set_value('helpdesk_url'),
);
echo form_input($inp);
echo "</li>";
echo "</ol>";
echo form_fieldset_close();
echo form_fieldset(lang('rr_technicalinformation'));
echo "<ol>";

/**
 * assertion consumer service
 */
echo "<li>";
echo form_label(lang('rr_assertioncosumerservice').showBubbleHelp(lang('rhelp_assertionconsumer')), 'assertionconsumer_binding');
echo form_dropdown('acs_bind', $acs_dropdown);
echo form_label(lang('rr_acsurl').showBubbleHelp(lang('rhelp_acsurl')),'acs_url');
$inp = array(
    'id' => 'acs_url',
    'name' => 'acs_url',
    'value' => set_value('acs_url'),
);
echo form_input($inp);
echo "&nbsp;&nbsp;index";
$inp = array(
    'id' => 'acs_order',
    'name' => 'acs_order',
    'lengh' => 3,
    'value' => set_value('acs_order', 1),
    'class' => 'acsindex',
);
echo form_input($inp);
echo "</li>";

/**
 * @todo dodac przycisk jquery ktory doda kolejny input 
 */
/**
 * certificate use=signing
 */
echo "<li>";
echo form_label(lang('rr_certificatesigning').' <small>('.lang('rr_optional').')</small>', 'sign_cert_body');
echo form_textarea(array(
    'id' => 'sign_cert_body',
    'name' => 'sign_cert_body',
    'value' => getPEM(set_value('sign_cert_body')),
    'cols' => 65, 
    'rows' => 30
));
echo "</li>";
/**
 * certificate use=encripting
 */
echo "<li>";
echo form_label(lang('rr_certificateencrypting').' <small>('.lang('rr_optional').')</small>', 'encrypt_cert_body');
echo form_textarea(array(
    'id' => 'encrypt_cert_body',
    'name' => 'encrypt_cert_body',
    'value' => getPEM(set_value('encrypt_cert_body')),
    'cols' => 65, 
    'rows' => 30
));

echo "</li>";
echo "</ol>";
echo form_fieldset_close();
/**
 * contact detail
 */
echo form_fieldset(lang('rr_primarycontact'));
echo "<ol>";
echo "<li>";
echo form_label(lang('rr_contactname'), 'contact_name');
echo form_input(array(
    'id' => 'contact_name',
    'name' => 'contact_name',
    'value' => set_value('contact_name')
));
echo "</li>";
echo "<li>";
$in3 = array('id' => 'contact_mail',
    'name' => 'contact_mail',
    'value' => set_value('contact_mail')
);
echo form_label(lang('rr_contactemail'), 'contact_mail');
echo form_input($in3);
echo "</li>";

echo "<li>";
$in4 = array(
    'id' => 'contact_phone',
    'name' => 'contact_phone',
    'value' => set_value('contact_phone'),
);

echo form_label(lang('rr_contactphone').' <small>('.lang('rr_optional').')</small>', 'contact_phone');
echo form_input($in4);
echo "</li>";
?>

<?php
echo "</ol>";
?>
<div class="buttons">
    <button type="submit" name="submit" value="Submit and wait for approval" class="btn positive">
        <span class="save"><?php echo lang('rr_submitwait'); ?><span></button>
                </div>
                <?php
                echo form_fieldset_close();

                echo form_close();



                echo "</div>";



                
