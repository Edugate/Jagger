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
    if (!empty($add_applet)) {
        ?>
    <table id="details">
        <caption>upload image</caption>
        <tbody>
            <tr>
                <td>
                    <div class="notice">Image will be available after approving by Team.</div>
        <center>
            <applet id="jumpLoaderApplet" name="jumpLoaderApplet"
                    code="jmaster.jumploader.app.JumpLoaderApplet.class"
                    archive="<?php echo base_url() . "app/jumploader_z.jar"; ?>"
                    width="715"
                    height="500"
                    mayscript>
                <param name="ac_fireAppletInitialized" value="true"/>
                <param name="uc_imageEditorEnabled" value="true"/>
                <param name="uc_uploadUrl" value="<?php echo base_url() . "app/phpuploader.php"; ?>"/>
                <param name="uc_partitionLength" value="1000000"/>
                <param name="ic_resizeOptions" value="150x150;150;150;100x225;100;225"/>
                <param name="ac_fireUploaderFilePartitionUploaded" value="true"/>
                <param name ="uc_retryFailedWhenStartUpload" value="true"/>
                <param name="uc_addImagesOnly" value="true"/>
                <param name="ac_messagesZipUrl" value="<?php echo base_url() . "app/messages.zip"; ?>"/>
                <param name="uc_maxfiles" value="1"/>



                <script language=javascript>

                    var app;

                    function appletInitialized( applet ) {
                        app=applet;
                        app.getViewConfig().setUploadViewRetryActionVisible( false );
                        app.updateView(getUploadView());
                    }

                    function getUploadView(){
        
                        return getMainView().getUploadView();
        
                    }

                    function getMainView(){
                        return app.getMainView();
                    }

                    function getApplet(){
        
                        return document.jumpLoaderApplet;
                    }

                    function uploaderFilePartitionUploaded( uploader, file ) {

                        if(uploader.isUploading())
                        {
                            setTimeout(message,800,uploader,file);

                        }else
                        {
                            message(uploader,file);
                        }
            
                    }

                    function message(uploader,file)
                    {
                        if(file.getStatus()==3){
                            g =app.getMainView();
                            g.showWarning("Your image dimensions are not correct, please use the editor.");
                        }
                    }

                </script>

            </applet>


        </center>
    </td></tr></tbody></table>
    <?php
}
