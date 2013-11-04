<?php
if(!empty($error))
{
	echo "<span class=\"error\">$error</span>";
	return;
}
if(!empty($message))
{
	echo "<span class=\"message\">$message</span>";
}
        $f_error = validation_errors('<div>', '</div>');
	$hidden = array('target' => $target);
	$attributes = array('class' => 'span-14', 'id' => 'formver2');
	echo form_open(current_url(), $attributes,$hidden);
        if(!empty($f_error))
        {
		echo "<div class=\"error\">";
		echo $f_error;
		echo "</div>";
        }
	echo "<fieldset><legend>Select identity provider</legend>";
	echo "<ol><li>";
	echo "<label for=\"identity_provider\">Identity Provider</label>";
	echo form_dropdown('identity_provider',$idplist_dropdown);
	echo "</li>";

?>
</ol>
</fieldset>

        <div class="buttons"><button type="submit" value="Submit" name="submit" id="submit" class="savebutton saveicon">Submit</button></div>
<?php
	echo form_close();
