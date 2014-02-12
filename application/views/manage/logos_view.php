<?php
if ($provider_detail['type'] == 'sp') {
    $imglink = 'block-share.png';
    $data['spid'] = $provider_detail['id'];
} else {
    $imglink = 'home.png';


    $data['idpid'] = $provider_detail['id'];
}
echo '<div id="pagetitle">'.lang('rr_logosmngt').'</div>';

?>

<div id="subtitle">
    
<?php 
echo $provider_detail['locked'];
echo  '<h3>'.anchor(base_url() . 'providers/detail/show/' . $provider_detail['id'],$provider_detail['name']) . '</h3><h4> ' . $provider_detail['entityid'] . '</h4>'; ?>
    <br />
<?php
if (!empty($backlink)) 
{
    echo lang('rr_backtoassignedlogos').' '.anchor(base_url() . "manage/logos/provider/" . $provider_detail['type'] . "/" . $provider_detail['id'], '<img src="' . base_url() . 'images/icons/arrow.png"/>'); 
}
?>
</div>
    <?php
    if (!empty($form1)) {
        echo $form1;
    }
if(!empty($show_upload) && !empty($upload_enabled))
{
?>
<div id="upload">
		<?php
                $hidden = array('origurl'=>current_url(),'upload'=>'upload');
                $attrs = array('id'=>'uploadlogo','name'=>'uploadlogo');
                if(!empty($infomessage))
                {
                   echo '<div>'.$infomessage.'</div>';
                }
		echo form_open_multipart(base_url().'manage/logos/uploadlogos',$attrs,$hidden);
                echo '<div class="uploadresult"></div>';
		//echo form_upload('userfile');
                echo '<input type="file" name="userfile" id="userfile" size="20" />';
                echo '<div class="buttons"><button type="submit" name="upload" value="upload" class="savebutton saveicon">'.lang('rr_upload').'</button></div>';
		echo form_close();
		?>		
</div>
<?php
}
