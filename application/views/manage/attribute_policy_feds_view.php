<?php

if (!empty($message))
{
    echo "<span class=\"message\">" . $message . "</span>";
}
if (!empty($error))
{
    echo "<span class=\"error\">" . $error . "</span>";
}


$attributes = array('class' => 'span-16', 'id' => 'formver1');
$hidden = array('idpid' => $idpid);
$target = base_url() . "manage/attribute_policy/show_feds/".$idpid;
	echo "<h3>".$idpname."</h3>";
    echo form_open($target, $attributes, $hidden);
    echo form_fieldset('Manage attribute release policy for federation');
    echo "<ol>\n";
    echo "<li>\n";
    echo form_label('Select federation ', 'fedid') . "\n";

    echo $federations;
    echo "</li>\n";


    echo "</ol>\n";
    echo form_fieldset_close();
    //echo form_submit('submit', 'Next');
    echo '<div class="buttons"><button type="submit" name="submit" id="submit" value="submit" class="btn positive"><span class="save">Next</span></button></div>';

    echo form_close();

