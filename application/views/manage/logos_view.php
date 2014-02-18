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
<div style="float: right; witdth: 99%; ">
<?php
if(!empty($addnewlogobtn))
{
   echo '<a href="' . $targeturl . '"><button name="add" type="button" value="Add new image" class="addbutton addicon" onclick="window.open(\'' . $targeturl . '\',\'_self\')">'.lang('rr_assignnewlogo').'</button></a>';

}
?>
</div>
<div style="clear: both;"></div>



<div>
<?php




if(!empty($show_upload) && !empty($upload_enabled))
{
     echo '<div id="upload" style="display: block-inline; float: right; ">';
     $hidden = array('origurl'=>current_url(),'upload'=>'upload','prvid'=>''.$provider_detail['id'].'','prvtype'=>''.$provider_detail['type'].'');
     $attrs = array('id'=>'uploadlogo','name'=>'uploadlogo');
     echo '<div style="position: relative;">';
     echo form_open_multipart(base_url().'manage/logos/uploadlogos',$attrs,$hidden);
     if(!empty($infomessage))
     {
         echo '<div class="help">'.$infomessage.'</div>';
     }
     echo '<div class="uploadresult notice"></div>';
     echo '<div>';
     echo form_label(lang('rr_extlogourl'),'extlogourl');
     echo '<input type="url" name="extlogourl" id="extlogourl" site="40" style="width: 90%" />';
     echo '</div>';
     echo '<div>';
     echo form_label(lang('rr_uploadlogo'),'userfile');
     echo '<input type="file" name="userfile" id="userfile" size="20" class="clearable" style="background: transparent" />';
     echo '</div>';
     echo '<div class="buttons"><button type="submit" name="upload" value="upload" class="savebutton saveicon">'.lang('rr_upload').'</button></div>';
     echo '<div class="availablelogosgrid" style="display:none">'.base_url().'manage/logos/getAvailableLogosInGrid</div>';
     echo form_close();
     echo '</div>';
}
?>		
</div>



<div style="clear: both;"><br /></div>

<?php
    if (!empty($form1))
    {
        echo $form1;
    }
