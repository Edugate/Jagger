<?php
 if(!empty($error))
 {
echo '<div class="error">';
echo $this->upload->display_errors('<p>', '</p');
echo '</div>';
 }
 if(!empty($message))
 {
     echo $message;
 }
if(!empty($backurl))
{
  echo '<br />'.anchor($backurl,''.lang('rr_goback').'');
}
?>
<div id="upload">
		<?php
		echo form_open_multipart(base_url().'manage/logos/uploadlogos');
		echo form_upload('userfile');
                echo '<div class="buttons"><button type="submit" name="upload" value="upload" class="savebutton saveicon">'.lang('rr_upload').'</button></div>';
		echo form_close();
		?>		
	</div>
