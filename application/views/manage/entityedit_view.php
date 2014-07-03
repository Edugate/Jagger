
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
if(!empty($sessform))
{
  echo '<div class="warning alert-box" alert-data>'.lang('formfromsess').'</div>';
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
        $active = false;
        if(!empty($registerForm))
        {
           echo '<li class="tab-title active"><a href="#general">General</a></li>';
           $active = true;

        }
        foreach ($menutabs as $m)
        {
            if(!$active && $m['id'] === 'organization')
            {
                echo '<li class="tab-title active"><a href="#' . $m['id'] . '">' . $m['value'] . '</a></li>';
                $active = true;
            }
            else
            {
                echo '<li class="tab-title"><a href="#' . $m['id'] . '">' . $m['value'] . '</a></li>';
            }
            
        }
        ?>
    </ul>

<div class="tabs-content">
    <?php


    $active=false;
    if(!empty($registerForm))
    {
        echo '<div id="general" class="content tabgroup active">';
        $active = true;

        /**
         * general info input like federation, contact
         */
        if(!empty($federations) && is_array($federations))
        {
          echo '<div class="small-12 columns">';
          echo '<div class="small-3 columns">'.jform_label(lang('rr_federation') . ' ' . showBubbleHelp(lang('rhelp_onlypublicfeds')) . '', 'f[federation]').'</div>';
          echo '<div class="small-6 large-7 columns end">'.form_dropdown('f[federation]', $federations,set_value('f[federation]')).'</div>';
          echo '</div>';
        }
        
        echo '</div>';

    }
    foreach ($menutabs as $m)
    {
        if(!$active && $m['id'] === 'organization')
        {
           echo '<div id="' . $m['id'] . '" class="content tabgroup active">';
           $active = true;
        }
        else
        {
           echo '<div id="' . $m['id'] . '" class="content tabgroup">';

        }
         
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
                   echo '<div class="small-12 columns">'.$g.'</div>';
                }
            }
        }



        /**
         * end form elemts
         */
        echo '</div>';
    }
    echo '</div>';
   if(empty($registerForm))
   {
    echo '<div class="buttons">
        <button type="submit" name="discard" value="discard" class="resetbutton reseticon">'.lang('discardall').'</button>
        <button type="submit" name="modify" value="savedraft" class="savebutton saveicon">'.lang('savedraft').'</button>
        <button type="submit" name="modify" value="modify" class="savebutton saveicon">'.lang('btnupdate').'
      </button></div>';
   }
   else
   {
    echo '<div class="buttons">
        <button type="submit" name="discard" value="discard" class="resetbutton reseticon">'.lang('btnstartagain').'</button>
        <button type="submit" name="modify" value="savedraft" class="savebutton saveicon">'.lang('savedraft').'</button>
        <button type="submit" name="modify" value="modify" class="savebutton saveicon">'.lang('btnregister').'
      </button></div>';



   }
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
