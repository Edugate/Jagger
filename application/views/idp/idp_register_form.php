<div id="jj">
    <?php
    $this->load->helper("cert");
    $base = base_url();
    $attributes = array('class' => 'email', 'id' => 'formver2');
    echo form_open("" . $base . "providers/idp_registration/submit", $attributes);
    $errors_v = validation_errors('<span class="span-12">', '</span><br />');
    if (!empty($errors_v)) {
        echo '<div class="error">';
        echo $errors_v;
        echo "</div>";
    }

    $required = "required=\"required\"";
    // $required = "";
    ?>

    <div id="step1">
        <fieldset>
            <legend><?php echo lang('rr_generalinformation');?></legend>

            <ol>

                <li>

                    <label for="homeorg"><?php echo lang('rr_homeorganisation');?></label>

                    <input type="text" id="homeorg" name="homeorg" placeholder="Example Home Org Name"
                           value="<?php echo set_value('homeorg'); ?>" tabindex="1" <?php echo $required; ?>/>
                </li>

                <li>

                    <label for="federation"><?php echo lang('rr_federation');?></label>


                    <?php
                    echo form_dropdown('federation', $federations);
                    ?>
                </li>

            </ol>

        </fieldset>
        <fieldset>
            <legend><?php echo lang('rr_technicalinformation');?></legend>
            <ol>
                <li>
                    <label for="metadataurl"><?php echo lang('rr_metadatalocation');?></label>
                    <input type="text" id="metadataurl" name="metadataurl" value="<?php echo set_value('metadataurl'); ?>"/>
                </li>
                <li>
                    <label for="entity"><?php echo lang('rr_entityid').showHelp(lang('rhelp_entityid'));?></label>
                    <input type="text" id="entity" name="entity" placeholder="https://idp.example.com/idp/shibboleth"
                           value="<?php echo set_value('entity'); ?>"  <?php echo $required; ?>/>
                </li>
                <li>
                    <label for="privacyurl"><?php echo lang('rr_privacystatement');?></label>
                    <input type="text" id="privacyurl" name="privacyurl" value="<?php echo set_value('privacyurl'); ?>"/>
                </li>
                <li>
                    <label for="scope"><?php echo lang('rr_scope');?></label>
                    <input type="text" id="scope" name="scope" placeholder="your domain"
                           value="<?php echo set_value('scope'); ?>"  <?php echo $required; ?>/>
                </li>
                <li>
                    <label for="bindingname"><?php echo lang('rr_bindingtypesinglesign').showHelp(lang('rhelp_bindingtype'));?></label>
                    <?php
                    $binding_values = $this->config->item('ssohandler_saml2');
                    echo form_dropdown('bindingname', $binding_values);
                    ?>
                </li>
                <li>
                    <label for="ssohandler"><?php echo lang('rr_singlesignonurl').showHelp(lang('rhelp_urlsinglesign'));?></label>
                    <input type="text" id="ssohandler" name="ssohandler" placeholder="https://idp.example.com/idp/profile/SAML2/Redirect/SSO"
                           value="<?php echo set_value('ssohandler'); ?>"  <?php echo $required; ?>/>
                </li>

                <li>
                    <label for="certbody"><?php echo lang('rr_idpsigningcert');?></label>
                    <textarea id="certbody" name="certbody" cols="65" rows="30"  <?php echo $required; ?>><?php echo getPEM(set_value('certbody')); ?></textarea>
                </li>
            </ol>
        </fieldset>

        <?php
        echo form_fieldset(lang('rr_primarycontact'));
        ?>		
        <ol>
            <li>

                <label for="contactname"><?php echo lang('rr_contactname');?></label>
                <input type="text" id="contactname" name="contactname" placeholder="First and last name"
                       value="<?php echo set_value('contactname'); ?>"   <?php echo $required; ?>/><br />
            </li>
            <li>
                <label for="contactmail"><?php echo lang('rr_contactemail');?></label>
                <input type="email" id="contactmail" name="contactmail" placeholder="example@domain.com"
                       value="<?php echo set_value('contactmail'); ?>"   <?php echo $required; ?>/><br />
            </li>
            <li>
                <label for="phone"><?php echo lang('rr_contactphone');?></label>
                <input type="text" id="phone" name="phone" 
                       value="<?php echo set_value('phone'); ?>" /><br />
            </li>
            <li>
                <label for="helpdeskurl"><?php echo lang('rr_helpdeskurl').showHelp(lang(' rhelp_helpdeskurl'));?></label>
                <input type="text" id="helpdeskurl" name="helpdeskurl"
                       value="<?php echo set_value('helpdeskurl'); ?>"   <?php echo $required; ?>/><br />
            </li>
            <li>
                <label for="homeurl"><?php echo lang('rr_homeorganisationurl');?></label>
                <input type="text" id="homeurl" name="homeurl"
                       value="<?php echo set_value('homeurl'); ?>" <?php echo $required; ?>/><br />
            </li>

        </ol>
        <div class="buttons">
            <button type="submit" name="submit" value="Submit and wait for approval" class="btn positive">
                <span class="save"><?php echo lang('rr_submitwait');?><span></button>
                        </div>
                        <?php
                        echo form_fieldset_close();
                        ?>




                        </div>  

                        <?php
                        echo form_close();
                        ?>
                        </div>
