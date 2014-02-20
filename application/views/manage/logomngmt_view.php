<?php
echo '<div id="pagetitle">'.lang('rr_logosmngt').'</div>';
?>
<div id="subtitle">
<?php 
echo  '<h3>'.anchor(base_url() . 'providers/detail/show/' . $provider_detail['id'],$provider_detail['name']) . '</h3><h4> ' . $provider_detail['entityid'] . '</h4>'; ?>
</div>
<div>
<?php


     if(!empty($infomessage))
     {
         echo '<div class="help" style="display: inline-block; width: 60%;">'.$infomessage.'</div>';
     }


if($canEdit === TRUE)
{
     echo '<div id="upload" style="display: block-inline; float: right; width: 40%; ">';
     $hidden = array('origurl'=>current_url(),'upload'=>'upload','prvid'=>''.$provider_detail['id'].'','prvtype'=>''.$provider_detail['type'].'');
     $attrs = array('id'=>'uploadlogo','name'=>'uploadlogo');
     echo '<div style="position: relative;">';
     echo form_open_multipart(base_url().'manage/logomngmt/uploadlogos',$attrs,$hidden);
     echo form_label(lang('rr_extlogourl'),'extlogourl');
     echo '<input type="url" name="extlogourl" id="extlogourl" site="40" style="width: 90%" />';
     if($upload_enabled)
     {
         echo form_label(lang('rr_uploadlogo'),'userfile');
         echo '<input type="file" name="userfile" id="userfile" size="20" class="clearable" style="background: transparent" />';
     }
     echo '<div class="buttons"><button type="submit" name="upload" value="upload" class="savebutton saveicon">'.lang('rr_add').'</button></div>';
     echo form_close();
     echo '</div>';
}
?>		
</div>



<div style="clear: both;"><br /></div>
<div style="clear: both;"></div>

<div class="uploadresult" style="display:none"></div>
<div id="logotabs">
<ul>
<?php
echo '<li><a href="#t1">'.lang('rr_assignedlogostab').'</a></li>';
if(!empty($showavailable))
{
  echo '<li class="availablelogostabs"><a href="'.base_url().'/manage/logomngmt/getAvailableLogosInGrid/'.$provider_detail['type'].'/'.$provider_detail['id'].'">'.lang('rr_availablelogostab').'</a></li>';
}
?>
</ul>

<?php
      echo '<div id="t1">';
   if(!empty($assignedlogos))
   {
      echo $assignedlogos;
   }
      echo '</div>';
?>
</div>
<?php
echo '<div class="assignedlogosgrid" style="display:none">'.base_url().'manage/logomngmt/getAssignedLogosInGrid/'.$provider_detail['type'].'/'.$provider_detail['id'].'</div>';
echo '<div class="availablelogosgrid" style="display:none">'.base_url().'manage/logomngmt/getAvailableLogosInGrid/'.$provider_detail['type'].'/'.$provider_detail['id'].'</div>';
