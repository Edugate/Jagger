<?php

$validationErrs = validation_errors('<div>', '</div>');


if (!empty($validationErrs) || !empty($error_messages2)) {
    echo '<div class="alert alert-box" alert-data>';
    if (!empty($validationErrs)) {
        echo $validationErrs;
    }
    if (!empty($error_messages2)) {
        echo $error_messages2;
    }
    echo '</div>';
}
if (!empty($sessform)) {
    echo '<div class="warning alert-box" alert-data>' . lang('formfromsess') . '</div>';
}
?>

<?php
$action = current_url();
$attrs = array('id' => 'providereditform');
//echo '<div class="dtabs-content">';
echo form_open($action, $attrs);


?>
<ul class="tabs" data-tabs id="entityedittabs">
    <?php
    $active = false;
    if (!empty($registerForm)) {
        echo '<li class="tabs-title is-active"><a href="#general">' . lang('tabgeneral') . '</a></li>';
        $active = true;

    }
    foreach ($menutabs as $m) {
        if (!$active && $m['id'] === 'organization') {
            echo '<li class="tabs-title is-active"><a href="#' . $m['id'] . '">' . $m['value'] . '</a></li>';
            $active = true;
        } else {
            echo '<li class="tabs-title"><a href="#' . $m['id'] . '">' . $m['value'] . '</a></li>';
        }

    }
    ?>
</ul>

<div class="tabs-content" data-tabs-content="entityedittabs">
    <?php


    $active = false;
    if (!empty($registerForm)) {
        echo '<div id="general" class="tabs-panel tabgroup is-active">';
        $active = true;

        /**
         * general info input like federation, contact
         */
        if (!empty($federations) && is_array($federations)) {
            echo '<div class="small-12 columns">';
            echo '<div class="small-3 columns">' . jform_label(lang('rr_federation') . ' ' . showBubbleHelp(lang('rhelp_onlypublicfeds')) . '', 'f[federation]') . '</div>';
            echo '<div class="small-6 large-7 columns end">' . form_dropdown('f[federation]', $federations, set_value('f[federation]')) . '</div>';
            echo '</div>';
        }

        /**
         * BEGIN display contact form if anonymous
         */

        if (!empty($anonymous)) {
            echo '<div class="small-12 columns"><div class="section">' . lang('yourcontactdetails') . '</div></div>';
            echo '<div class="small-12 columns">';
            echo '<div class="small-3 columns">' . jform_label(lang('rr_contactfirstname'), 'f[primarycnt][fname]') . '</div>';
            echo '<div class="small-6 large-7 columns end">' . form_input(array('id' => 'f[primarycnt][fname]', 'name' => 'f[primarycnt][fname]', 'value' => set_value('f[primarycnt][fname]', '', FALSE))) . '</div>';
            echo '</div>';
            echo '<div class="small-12 columns">';
            echo '<div class="small-3 columns">' . jform_label(lang('rr_contactlastname'), 'f[primarycnt][lname]') . '</div>';
            echo '<div class="small-6 large-7 columns end">' . form_input(array('id' => 'f[primarycnt][lname]', 'name' => 'f[primarycnt][lname]', 'value' => set_value('f[primarycnt][lname]', '', FALSE))) . '</div>';
            echo '</div>';
            echo '<div class="small-12 columns">';
            echo '<div class="small-3 columns">' . jform_label(lang('rr_contactemail'), 'f[primarycnt][mail]') . '</div>';
            echo '<div class="small-6 large-7 columns end">' . form_input(array('id' => 'f[primarycnt][mail]', 'name' => 'f[primarycnt][mail]', 'value' => set_value('f[primarycnt][mail]', '', FALSE))) . '</div>';
            echo '</div>';
            echo '<div class="small-12 columns">';
            echo '<div class="small-3 columns">' . jform_label(lang('rr_contactphone'), 'f[primarycnt][phone]') . '</div>';
            echo '<div class="small-6 large-7 columns end">' . form_input(array('id' => 'f[primarycnt][phone]', 'name' => 'f[primarycnt][phone]', 'value' => set_value('f[primarycnt][phone]', '', FALSE))) . '</div>';
            echo '</div>';
        } else {
            echo '<div class="small-12 columns"><div class="section">' . lang('yourcontactdetails') . '</div></div>';
            echo '<div class="small-12 columns">';
            echo '<div class="small-3 columns">' . jform_label(lang('rr_username'), 'f[primarycnt][username]') . '</div>';
            echo '<div class="small-6 large-7 columns end">' . form_input(array('id' => 'f[primarycnt][username]', 'name' => 'f[primarycnt][username]', 'disabled' => 'disabled', 'readonly' => 'readonly', 'value' => '' . $loggeduser['username'] . '')) . '</div>';
            echo '</div>';
            echo '<div class="small-12 columns">';
            echo '<div class="small-3 columns">' . jform_label(lang('rr_contactfirstname'), 'f[primarycnt][fname]') . '</div>';
            echo '<div class="small-6 large-7 columns end">' . form_input(array('id' => 'f[primarycnt][fname]', 'name' => 'f[primarycnt][fname]', 'disabled' => 'disabled', 'readonly' => 'readonly', 'value' => '' . $loggeduser['fname'] . '')) . '</div>';
            echo '</div>';
            echo '<div class="small-12 columns">';
            echo '<div class="small-3 columns">' . jform_label(lang('rr_contactlastname'), 'f[primarycnt][lname]') . '</div>';
            echo '<div class="small-6 large-7 columns end">' . form_input(array('id' => 'f[primarycnt][lname]', 'name' => 'f[primarycnt][lname]', 'disabled' => 'disabled', 'readonly' => 'readonly', 'value' => '' . $loggeduser['lname'] . '')) . '</div>';
            echo '</div>';
            echo '<div class="small-12 columns">';
            echo '<div class="small-3 columns">' . jform_label(lang('rr_contactemail'), 'f[primarycnt][mail]') . '</div>';
            echo '<div class="small-6 large-7 columns end">' . form_input(array('id' => 'f[primarycnt][mail]', 'name' => 'f[primarycnt][mail]', 'disabled' => 'disabled', 'readonly' => 'readonly', 'value' => '' . $loggeduser['email'] . '')) . '</div>';
            echo '</div>';


        }
        /**
         * END display contact form if anonymous
         */

        echo '</div>';

    }
    foreach ($menutabs as $m) {
        if (!$active && $m['id'] === 'organization') {
            echo '<div id="' . $m['id'] . '" class="tabs-panel tabgroup is-active">';
            $active = true;
        } else {
            echo '<div id="' . $m['id'] . '" class="tabs-panel tabgroup">';

        }

        /**
         * start form elemts
         */
        if (!empty($m['form']) && is_array($m['form'])) {
            $counter = 0;
            foreach ($m['form'] as $g) {
                if (empty($g)) {
                    if ($counter % 2 == 0) {
                        echo '<div class="row group">';
                    } else {
                        echo '</div>';
                    }
                    $counter++;
                } else {
                    echo '<div class="row ">' . $g . '</div>';
                }
            }
        }


        /**
         * end form elemts
         */
        echo '</div>';
    }
    echo '</div>';
    echo '<div class="row">';
    echo '<div class="small-12 column text-center">';
    echo '<div class="button-group expanded">';
    if (empty($registerForm)) {

        echo ' 
        <button type="submit" name="discard" value="discard" class="button resetbutton reseticon alert">' . lang('rr_cancel') . '</button>
        <button type="submit" name="modify" value="savedraft" class="button savebutton saveicon secondary">' . lang('savedraft') . '</button>
        <button type="submit" name="modify" value="modify" class="button savebutton saveicon">' . lang('btnupdate') . '</button>
     ';
    } else {
    
        echo '
        <button type="submit" name="discard" value="discard" class="button resetbutton reseticon alert">' . lang('btnstartagain') . '</button>
        <button type="submit" name="modify" value="savedraft" class="button savebutton saveicon secondary">' . lang('savedraft') . '</button>
        <button type="submit" name="modify" value="modify" class="button savebutton saveicon">' . lang('btnregister') . '</button></div>';
    }
    echo '</div></div></div>';
    ?>


    </form>
 </div> 
<div style="display: none" id="entitychangealert"><?php echo lang('alertentchange'); ?></div>
<?php
echo '<button id="helperbutttonrm" type="button" class="button langinputrm hidden  left inline button">' . lang('rr_remove') . '</button>';
?>
<script type="text/javascript">

    function stopRKey(evt) {
        var evt = (evt) ? evt : ((event) ? event : null);
        var node = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null);
        if ((evt.keyCode == 13) && (node.type == "text")) {
            return false;
        }
    }

    document.onkeypress = stopRKey;

</script> 
