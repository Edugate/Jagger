
<div class="small-12 column">
<?php
 $errors_v = validation_errors('<div>', '</div>');
    if (!empty($errors_v)) {
        echo '<div data-alert class="alert-box alert">';
        echo $errors_v;
        echo "</div>";
    }
$attributes = array('class' => 'email', 'id' => 'formver2');
echo form_open(''. base_url() . 'manage/statdefs/statdefedit/'.$providerid.'/'.$statdefid, $attributes);

?>
<fieldset>
 <legend><?php echo lang('rr_statdefbasicgroup');?></legend>
   <div class="small-12 columns">
     <div class="small-3 columns"><label for="defname" class="right inline"><?php echo lang('rr_statdefshortname');?></label></div>
     <div class="small-6 large-7 columns end"><input type="text" id="defname" name="defname" required="required" value="<?php echo set_value('defname',$statdefshortname);?>"/></div>
   </div>
   <div class="small-12 columns">
   
     <div class="small-3 columns"><label for="titlename" class="right inline"><?php echo lang('rr_statdeftitle');?></label></div>
     <div class="small-6 large-7 columns end"><input type="text" id="titlename" name="titlename" required="required" value="<?php echo set_value('titlename',$statdeftitle);?>"/></div>
   </div>
   <div class="small-12 columns">

     <div class="small-3 columns"><label for="description" class="right inline"><?php echo lang('rr_statdefdesc');?></label></div>
     <div class="small-6 large-7 columns end"><textarea id="description" name="description" required="required" cols="65" rows="10"><?php echo set_value('description',$statdefdesc);?></textarea></div>
   </div>
   <div class="small-12 columns">
     <div class="small-3 columns"><label for="overwrite" class="right inline"><?php echo lang('rr_overwritestatfile');?></label></div>
     <div class="small-6 large-7 columns end"><input type="checkbox" name="overwrite" id="overwrite" value="yes" <?php echo set_checkbox('overwrite', 'yes',$statdefoverwrite); ?> style="margin:10px" /></div>
   </div>
</fieldset>
<?php 
if(empty($showpredefined) && !empty($statdefpredef) && !empty($statdefpredefworker))
{
      echo '<div data-alert class="alert-box alert"><p><b>'.$statdefpredefworker.'</b>: '.lang('noselpredefstattype').'</p></div>';
}
elseif(!empty($showpredefined) && $showpredefined === TRUE)
   {
?>
      <fieldset>
      <legend><?php echo lang('builtinstatdefs');?></legend>
<?php
      echo '<div>'.lang('youcanshoose').':<br />'.$workersdescriptions.'</div>';
?>
       <div class="small-12 columns">

      <div class="small-3 columns"><label for="usepredefined" class="right inline"><?php echo lang('plsusepredefstat') ;?></label></div>
             <div class="small-6 large-7 columns end"><input type="checkbox" name="usepredefined" id="usepredefined" value="yes" <?php echo set_checkbox('usepredefined', 'yes',$statdefpredef); ?> style="margin:10px" /></div>
      </div>
      <div class="small-12 columns">
           <div class="small-3 columns"><label for="gworker" class="right inline"><?php echo lang('listavailableprestats');?></label></div>
    <?php
 if(empty($statdefpredefworker))
 {
    echo '<div class="small-6 large-7 columns end">'.form_dropdown('gworker',$workerdropdown,$this->input->post('gworker'), "id='gworker'").'</div>';
 }
 else
 {
    if(!in_array($statdefpredefworker,$workerdropdown) && !$this->input->post('gworker'))
    {
       echo form_dropdown('gworker',$workerdropdown,$this->input->post('gworker'), "id='gworker'");
       echo '<div data-alert class="alert-box alert">your predefined worker <b>'.$statdefpredefworker.'</b> doesnt exists in the system anymore</div>';
    }
    else
    {
       $r = $this->input->post('gworker');
       if(empty($r))
       {
          $r = $statdefpredefworker;
       }
       echo form_dropdown('gworker',$workerdropdown,$statdefpredefworker, "id='gworker'");
    }
 }
?>
</li>
</ol>
</fieldset>
<?php
}
?>
<fieldset id="stadefext">
<?php 
  if(!empty($showpredefined) && $showpredefined === TRUE)
  {
?>
 <legend><?php echo lang('rr_statdefconngroup'). ' '.showBubblehelp(''.lang('rhelp_extstatleg').'').'';?></legend>
<?php
  }
  else
  {
?>
 <legend><?php echo lang('rr_statdefconngroup') ;?></legend>

<?php
  }
?>
    <div class="small-12 columns">
     <div class="small-3 columns"><label for="sourceurl" class="right inline"><?php echo lang('rr_statdefsourceurl') .' '.showBubbleHelp(''.lang('rr_allowedtransfprots').': http,https').'';?> </label></div>
     <div class="small-6 large-7 columns end"><input type="text" id="sourceurl" name="sourceurl"  value="<?php echo set_value('sourceurl',$statdefsourceurl);?>"/></div>
    </div>
    <div class="small-12 columns">

      <div class="small-3 columns"><label for="httpmethod" class="right inline"><?php echo lang('rr_httpmethod');?></label></div>
     <?php
        $m = $this->input->post('httpmethod');
        if(empty($m) || !in_array($m,array('post','get')))
        {
           $m = $statdefmethod;
        }
        echo '<div class="small-6 large-7 columns end">'.form_dropdown('httpmethod',array('get'=>'GET','post'=>'POST'),$m, "id='httpmethod'").'</div>';
      ?>
    </div>
    
     <?php
       $example = 'attr1=value1, attr2=value2, attr3=value3';
       $exampleconvert= 'attr1<b>&#36;:&#36;</b>value1<b>&#36;&#36;</b>attr2<b>&#36;:&#36;</b>value2<b>&#36;&#36;</b>attr3<b>&#36;:&#36;</b>value3';
    ?>
     <div class="small-12 columns">
     <div class="small-3 columns"><label for="postoptions" class="right inline"><?php echo lang('rr_postoptions').' '.showBubbleHelp(''.lang('rr_postoptionshelp').'<br />'.lang('rr_example').''.lang('if').':'.$example.'<br />'.lang('write').': '.$exampleconvert.'');?></label></div>

     <div class="small-6 large-7 columns end"><textarea id="postoptions" name="postoptions"><?php echo set_value('postoptions',$statdefpostparam); ?></textarea></div>

    </div>
     <div class="small-12 columns"><div class="small-3 columns">
     <label for"formattype" class="right inline"><?php echo lang('rr_statdefformat'); ?></label></div>
     <?php
       $formats = array('image'=>'image (png, jpg, gif)','svg'=>'image (svg)');
       $sformat = $this->input->post('formattype');
       if(empty($sformat))
       {
          $sformat = $statdefformattype;
       }
       echo '<div class="small-6 large-7 columns end">'.form_dropdown('formattype',$formats, $sformat, "id='formattype'").'</div>';
     ?>
    </div>
    <div class="small-12 columns">
      <div class="small-3 columns"><label for="accesstype" class="right inline"><?php echo lang('rr_statdefaccess');?></label></div>
      <?php
        $accesstype = $this->input->post('accesstype');
        if(empty($accesstype))
        {
            $accesstype = $statdefaccesstype;
        }
        echo '<div class="small-6 large-7 columns end">'.form_dropdown('accesstype',array('anon'=>''.lang('rr_anon').'','basicauthn'=>''.lang('rr_basicauthn').''),$accesstype, "id='accesstype'").'</div>';
      ?>
    </div>
    <div class="small-12 columns">
       <div class="small-3 columns"><label for="userauthn" class="right inline"><?php echo lang('rr_username') .' '.showBubbleHelp(''.lang('rhelp_stadefuserauthn').'') ;?> </label></div>
      <div class="small-6 large-7 columns end"><input type="text" id="userauthn" name="userauthn" value="<?php echo set_value('userauthn',$statdefauthuser);?>"/></div>
    </div>
    <div class="small-12 columns">
      <div class="small-3 columns"><label for="passauthn" class="right inline"><?php echo lang('rr_password') .' '.showBubbleHelp(''.lang('rhelp_stadefuserauthn').'') ;?> </label></div>
      <div class="small-6 large-7 columns end"><input type="password" id="passauthn" name="passauthn" value="<?php echo set_value('passauthn',$statdefpass);?>"/></div>
    </div>
   
  
</fieldset>
  <div class="buttons small-12 columns">
      <div class="small-9 large-10 columns end text-right"><button type="submit" name="submit" value="submit" class="savebutton saveicon small">
            <?php echo lang('btnupdate');?></button>

  </div></div> 

<?php
echo form_close();
?>

</div>
