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
            foreach ($m['form'] as $g)
            {
                echo '<li>';
                echo $g;
                echo '</li>';
            }
        }



        /**
         * end form elemts
         */
        echo '</ol>';
        echo '</fieldset>';
    }
    echo '<div class="buttons">
        <button type="submit" name="discard" value="discard" class="button negative"><span class="reset">'.lang('discardall').'</span></button>
        <button type="submit" name="modify" value="savedraft" class="button positive"><span class="save">'.lang('savedraft').'</span></button>
        <button type="submit" name="modify" value="modify" class="button positive"><span class="save">'.lang('btnupdate').'</span>
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
