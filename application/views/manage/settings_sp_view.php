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
	echo "<fieldset><legend>Select service provider</legend>";
	echo "<ol><li>";
	echo "<label for=\"service_provider\">Service Provider</label>";
	echo form_dropdown('service_provider',$splist_dropdown);
	echo "</li>";

?>
   <li>
</ol>
</fieldset>

        <div class="buttons"><button type="submit" value="Submit" name="submit" id="submit" class="btn positive"><span class="save">Submit</span></button></div>
<?php
	echo form_close();
