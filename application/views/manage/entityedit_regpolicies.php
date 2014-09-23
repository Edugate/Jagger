<?php
if(!empty($error_messages) || !empty($error_messages2))
{
   echo '<div class="alert alert-box" alert-data>';
   if (!empty($error_messages))
   {
      echo $error_messages;
   }
   if (!empty($error_messages2))
   {
      echo $error_messages2;
   }
   echo '</div>';
}

echo form_open();
foreach($r as $v)
{

 echo $v;
}
echo '<div class="small-12 columns"></div>';
echo '<div class="small-12 small-centered columns"><ul class="button-group text-center">';
 echo '
        <li><input type="submit" name="discard" value="'.lang('rr_cancel').'" class="button small alert" /></li>
        <li><input type="submit" name="modify" value="'.lang('btnupdate').'" class="button small"/></li>';
echo '</ul></div>';
echo form_close();
