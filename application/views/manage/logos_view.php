<?php
if ($provider_detail['type'] == 'sp') {
    $imglink = 'block-share.png';
    $data['spid'] = $provider_detail['id'];
    $this->load->view('/navigations/floatnav_sp_logos_view', $data);
} else {
    $imglink = 'home.png';


    $data['idpid'] = $provider_detail['id'];
    $this->load->view('/navigations/floatnav_idp_logos_view', $data);
}

?>

<div id="subtitle">
    
<?php 
echo $provider_detail['locked'];
echo $sub .' '. $provider_detail['name'] . '<i> (' . $provider_detail['entityid'] . ')</i>' . anchor(base_url() . "providers/provider_detail/" . $provider_detail['type'] . "/" . $provider_detail['id'], '<img src="' . base_url() . 'images/icons/' . $imglink . '" />'); ?>
    <br />
<?php
if (!empty($backlink)) {
    ?>
        Back to assigned logos list <?php echo anchor(base_url() . "manage/logos/provider/" . $provider_detail['type'] . "/" . $provider_detail['id'], '<img src="' . base_url() . 'images/icons/arrow.png"/>'); ?>
        <?php
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
                $hidden = array('origurl'=>current_url());
                if(!empty($infomessage))
                {
                   echo '<div>'.$infomessage.'</div>';
                }
		echo form_open_multipart(base_url().'manage/logos/uploadlogos','',$hidden);
		echo form_upload('userfile');
                echo '<div class="buttons"><button type="submit" name="upload" value="upload" class="btn positive"><span class="save">Upload</save></button></div>';
		echo form_close();
		?>		
</div>
<?php
}
