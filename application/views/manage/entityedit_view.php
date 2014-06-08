
<?php
if(!empty($error_messages) || !empty($error_messages2))
{
   echo '<div class="alert alert-box" alert-data><pre>';
   if (!empty($error_messages))
   {
      echo $error_messages;
   }
   if (!empty($error_messages2))
   {
      echo $error_messages2;
   }
   echo '</pre></div>';
}
if(!empty($sessform))
{
  echo '<div class="warning alert-box" alert-data>'.lang('formfromsess').' <a href="#" class="close">&times;</a></div>';
}
?>

<?php
    $action = current_url();
    $attrs = array('id' => 'formver2');
    echo '<div class="tabs-content">';
    echo form_open($action, $attrs);


?>
    <ul class="tabs" data-tab>
        <?php
        foreach ($menutabs as $m)
        {
            echo '<li class="tab-title"><a href="#' . $m['id'] . '">' . $m['value'] . '</a></li>';
        }
        ?>
    </ul>

<div class="tabs-content">
    <?php


    foreach ($menutabs as $m)
    {
        echo '<div id="' . $m['id'] . '" class="content tabgroup row">';
        /**
         * start form elemts
         */
        if (!empty($m['form']) and is_array($m['form']))
        {
            $counter = 0;
            foreach ($m['form'] as $g)
            {
                if(empty($g))
                {
                   if($counter % 2 == 0)
                   {
                        echo '<div class="group">';
                   }
                   else
                   {
                        echo '</div>';
                   }
                   $counter++;
                }
                else
                {
                   echo '<div class="large-12 small-12 columns">'.$g.'</div>';
                }
            }
        }



        /**
         * end form elemts
         */
        echo '</div>';
    }
    echo '</div>';
    echo '<div class="buttons">
        <button type="submit" name="discard" value="discard" class="resetbutton reseticon">'.lang('discardall').'</button>
        <button type="submit" name="modify" value="savedraft" class="savebutton saveicon">'.lang('savedraft').'</button>
        <button type="submit" name="modify" value="modify" class="savebutton saveicon">'.lang('btnupdate').'
      </button></div>';
    ?>




</form>
</div>
<div style="display: none" id="entitychangealert"><?php echo lang('alertentchange');?></div>
<?php
echo '<button id="helperbutttonrm" type="button" class="btn langinputrm hidden tiny left inline button">'.lang('rr_remove').'</button>';
?>
<script type="text/javascript">

function stopRKey(evt) {
  var evt = (evt) ? evt : ((event) ? event : null);
  var node = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null);
  if ((evt.keyCode == 13) && (node.type=="text"))  {return false;}
}

document.onkeypress = stopRKey;

</script> 
