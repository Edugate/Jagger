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
	$hidden = array('target' => $target);
	$attributes = array('class' => 'email', 'id' => 'formver2');
	echo form_open(current_url(), $attributes,$hidden);
	echo "<div class=\"error\">";
	echo validation_errors('<p class="error">', '</p>');
	echo "</div>";
	echo "<fieldset><legend>Select federation</legend>";
	echo "<ol><li>";
	echo "<label for=\"federation\">Federation</label>";
	echo form_dropdown('federation',$fedlist_dropdown);
	echo "</li>";

?>
   <li>
                    <label for="submit"></label>

                   <span class="buttons"><button type="submit" name="submit" id="submit" value="submit" class="btn positive"><span>Submit</span></button></span> 
                </li>
</ol>
<?php
	echo form_close();
