
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
    $attrs = array('id' => 'providereditform');
    echo '<div class="tabs-content">';
    echo form_open($action, $attrs);


?>
    <ul class="tabs" data-tab>
        <?php
        $active = false;
        if(!empty($registerForm))
        {
           echo '<li class="tab-title active"><a href="#general">'.lang('tabgeneral').'</a></li>';
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

        /**
         * BEGIN display contact form if anonymous
         */

         if(!empty($anonymous))
         {
            echo '<div class="small-12 columns"><div class="section">'.lang('yourcontactdetails').'</div></div>';
            echo '<div class="small-12 columns">';
            echo  '<div class="small-3 columns">'.jform_label(lang('rr_contactfirstname'),'f[primarycnt][fname]').'</div>'; 
            echo '<div class="small-6 large-7 columns end">'.form_input(array('id'=>'f[primarycnt][fname]','name'=>'f[primarycnt][fname]','value'=>set_value('f[primarycnt][fname]','',FALSE))).'</div>';
            echo '</div>';
            echo '<div class="small-12 columns">';
            echo  '<div class="small-3 columns">'.jform_label(lang('rr_contactlastname'),'f[primarycnt][lname]').'</div>'; 
            echo '<div class="small-6 large-7 columns end">'.form_input(array('id'=>'f[primarycnt][lname]','name'=>'f[primarycnt][lname]','value'=>set_value('f[primarycnt][lname]','',FALSE))).'</div>';
            echo '</div>';
            echo '<div class="small-12 columns">';
            echo  '<div class="small-3 columns">'.jform_label(lang('rr_contactemail'),'f[primarycnt][mail]').'</div>'; 
            echo '<div class="small-6 large-7 columns end">'.form_input(array('id'=>'f[primarycnt][mail]','name'=>'f[primarycnt][mail]','value'=>set_value('f[primarycnt][mail]','',FALSE))).'</div>';
            echo '</div>';
            echo '<div class="small-12 columns">';
            echo  '<div class="small-3 columns">'.jform_label(lang('rr_contactphone'),'f[primarycnt][phone]').'</div>'; 
            echo '<div class="small-6 large-7 columns end">'.form_input(array('id'=>'f[primarycnt][phone]','name'=>'f[primarycnt][phone]','value'=>set_value('f[primarycnt][phone]','',FALSE))).'</div>';
            echo '</div>';
         }
         else
         {
            echo '<div class="small-12 columns"><div class="section">'.lang('yourcontactdetails').'</div></div>';
            echo '<div class="small-12 columns">';
            echo  '<div class="small-3 columns">'.jform_label(lang('rr_username'),'f[primarycnt][username]').'</div>'; 
            echo '<div class="small-6 large-7 columns end">'.form_input(array('id'=>'f[primarycnt][username]','name'=>'f[primarycnt][username]','disabled'=>'disabled','readonly'=>'readonly','value'=>''.$loggeduser['username'].'')).'</div>'; 
            echo '</div>';
            echo '<div class="small-12 columns">';
            echo  '<div class="small-3 columns">'.jform_label(lang('rr_contactfirstname'),'f[primarycnt][fname]').'</div>'; 
            echo '<div class="small-6 large-7 columns end">'.form_input(array('id'=>'f[primarycnt][fname]','name'=>'f[primarycnt][fname]','disabled'=>'disabled','readonly'=>'readonly','value'=>''.$loggeduser['fname'].'')).'</div>'; 
            echo '</div>';
            echo '<div class="small-12 columns">';
            echo  '<div class="small-3 columns">'.jform_label(lang('rr_contactlastname'),'f[primarycnt][lname]').'</div>'; 
            echo '<div class="small-6 large-7 columns end">'.form_input(array('id'=>'f[primarycnt][lname]','name'=>'f[primarycnt][lname]','disabled'=>'disabled','readonly'=>'readonly','value'=>''.$loggeduser['lname'].'')).'</div>'; 
            echo '</div>';
            echo '<div class="small-12 columns">';
            echo  '<div class="small-3 columns">'.jform_label(lang('rr_contactemail'),'f[primarycnt][mail]').'</div>'; 
            echo '<div class="small-6 large-7 columns end">'.form_input(array('id'=>'f[primarycnt][mail]','name'=>'f[primarycnt][mail]','disabled'=>'disabled','readonly'=>'readonly','value'=>''.$loggeduser['email'].'')).'</div>'; 
            echo '</div>';
            

         }
        /**
         * END display contact form if anonymous
         */
        
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
    echo '<div class="small-12 column small-centered">
       <ul class="button-group radius">
        <li><button type="submit" name="discard" value="discard" class="resetbutton reseticon alert">'.lang('rr_cancel').'</button></li>
        <li><button type="submit" name="modify" value="savedraft" class="savebutton saveicon secondary">'.lang('savedraft').'</button></li>
        <li><button type="submit" name="modify" value="modify" class="savebutton saveicon">'.lang('btnupdate').'
      </button></li></ul></div>';
   }
   else
   {
    echo '<div class="buttons center small-12 column text-center">
        
        <button type="submit" name="discard" value="discard" class="resetbutton reseticon alert">'.lang('btnstartagain').'</button>
        <button type="submit" name="modify" value="savedraft" class="savebutton saveicon secondary">'.lang('savedraft').'</button>
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
