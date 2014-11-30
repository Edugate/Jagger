<div>
<?php

     echo '<div class="small-12 columns">';
     if(!empty($infomessage))
     {
         echo '<div class="help large-7 columns">'.$infomessage.'</div>';
     }
     else
     {
        echo '<div class="large-7 columns">&nbsp</div>';
     }


if($canEdit === TRUE)
{
     echo '<div id="upload" class="large-5 columns" ">';
     $hidden = array('origurl'=>current_url(),'upload'=>'upload','prvid'=>''.$provider_detail['id'].'','prvtype'=>''.$provider_detail['type'].'');
     $attrs = array('id'=>'uploadlogo','name'=>'uploadlogo');
     echo '<div style="position: relative;">';
     echo form_open_multipart(base_url().'manage/logomngmt/uploadlogos',$attrs,$hidden);
     echo form_label(lang('rr_extlogourl'),'extlogourl');
     echo '<input type="url" name="extlogourl" id="extlogourl" site="40" style="width: 90%" />';
     if($upload_enabled)
     {
         echo '<div class="small-12 columns"><div class="large-3 columns">'.form_label(lang('rr_uploadlogo'),'userfile').'</div>';
         echo '<div class="large-9 columns"><input type="file" name="userfile" id="userfile" size="20" class="clearable" style="background: transparent" /></div></div>';
     }
     echo '<div class="buttons small-12 columns text-right"><button type="submit" name="upload" value="upload" class="savebutton saveicon">'.lang('rr_add').'</button></div>';
     echo form_close();
     echo '</div>';
}
?>		
</div>
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

?>
<div id="messagereveal" class="reveal-modal small" data-reveal>
    <p class="infomsg"></p>
   <p><button class="modal-close button mall">Close</a></button>
  <a class="close-reveal-modal">&#215;</a>
</div>