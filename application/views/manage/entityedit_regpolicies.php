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

echo '<div class="small-12 column button-group">';
echo '
        <div class="small-6 column text-right"><a href="'.base_url().'providers/detail/show/'.$providerid.'" class="button small alert">'.lang('rr_cancel').'</a></div>
        <div class="small-6 column"><input type="submit" name="modify" value="'.lang('btnupdate').'" class="button small"/></div>';
echo '</div>';
echo '<input type="hidden" name="entregpolform" value="'.$providerid.'">';

echo form_close();
