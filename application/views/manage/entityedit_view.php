<div id="subtitle"><h3><a href="<?php echo base_url() . 'providers/detail/show/' . $entdetail['id']; ?>"><?php echo $entdetail['displayname']; ?></a></h3><h4><?php echo $entdetail['entityid']; ?></h4> </div>

<?php
echo '<div class="alert"><pre>';
if (!empty($error_messages))
{
    echo $error_messages;
}
if (!empty($error_messages2))
{
    echo $error_messages2;
}
echo '</pre></div>';
?>
<div id="formtabs">
    <ul>
        <?php
        foreach ($menutabs as $m)
        {
            echo '<li><a href="#' . $m['id'] . '">' . $m['value'] . '</a></li>';
        }
        ?>
    </ul>
    <?php
    $action = current_url();
    $attrs = array('id' => 'formver2');
    echo form_open($action, $attrs);


    foreach ($menutabs as $m)
    {
        echo '<fieldset id="' . $m['id'] . '"><legend>' . $m['value'] . '</legend>';
        echo '<ol>';
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
                        echo '<ol class="group">';
                   }
                   else
                   {
                        echo '</ol>';
                   }
                   $counter++;
                }
                else
                {
                   echo '<li>'.$g.'</li>';
                }
            }
        }



        /**
         * end form elemts
         */
        echo '</ol>';
        echo '</fieldset>';
    }
    echo '<div class="buttons">
        <button type="submit" name="discard" value="discard" class="resetbutton reseticon">'.lang('discardall').'</button>
        <button type="submit" name="modify" value="savedraft" class="savebutton saveicon">'.lang('savedraft').'</button>
        <button type="submit" name="modify" value="modify" class="savebutton saveicon">'.lang('btnupdate').'
      </button></div>';
    ?>




</form>
</div>
<div style="display: none" id="entitychangealert"><?php echo lang('alertentchange');?></div>
<script type="text/javascript">

function stopRKey(evt) {
  var evt = (evt) ? evt : ((event) ? event : null);
  var node = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null);
  if ((evt.keyCode == 13) && (node.type=="text"))  {return false;}
}

document.onkeypress = stopRKey;

</script> 
