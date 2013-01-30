<?php
 if(!empty($error))
 {
echo '<div class="error">';
echo $this->upload->display_errors('<p>', '</p');
   //  echo $error;
echo '</div>';
 }
 if(!empty($message))
 {
     echo $message;
 }
if(!empty($backurl))
{
  echo '<br />'.anchor($backurl,'Go back');
}
?>
<div id="upload">
		<?php
		echo form_open_multipart(base_url().'manage/logos/uploadlogos');
		echo form_upload('userfile');
		#echo form_submit('upload', 'Upload');
                echo '<div class="buttons"><button type="submit" name="upload" value="upload" class="btn positive"><span class="save">Upload</save></button></div>';
		echo form_close();
		?>		
	</div>
