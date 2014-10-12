<?php


if(!empty($warnmessage))
{
    echo '<div data-alert class="alert-box warning">'.$warnmessage.'</div>';
 
}
?>
<div class="small-12 columns text-right"><button id="registernotification2" class="addbutton addicon" type="button" ><?php echo lang('rr_add');?></button></div>
<?php
  if(count($rows)>1)
  {
   $tmpl = array('table_open' => '<table id="detailsnosort" class="zebra">');
   $this->table->set_template($tmpl);
   echo '<div class="small-12 columns">';
   echo $this->table->generate($rows); 
   echo '</div>';
   $this->table->clear();
  }

/**
 * update form
 */
   $rrs = array('id'=>'notificationupdateform');

   echo '<div id="notificationupdatemodal" class="reveal-modal medium" data-reveal>';
   echo form_open(base_url().'notification/subscriber/updatestatus/',$rrs);
   echo form_input(array('name'=>'noteid','id'=>'noteid','type'=>'hidden','value'=>''));
   ?>
      <div class="header">
      <span><?php echo lang('updnotifstatus'); ?></span>
      </div>
      <div></div>
      <p class="message"></p>
     <div>
      <?php
       echo form_dropdown('status', $statusdropdown,set_value('status'));
     ?>
    </div>
      <div class="buttons">
      <button class="alert"><?php echo lang('rr_cancel');?></button>
      <button type="submit" name="updstatus"><?php echo lang('btnupdate');?></button>
     </div>
   <?php
   echo form_close();
   echo' <a class="close-reveal-modal">&#215;</a>';
   echo '</div>';

/**
 * add form
 */
   $rrs = array('id'=>'notificationaddform');

   echo '<div id="notificationaddmodal" class="reveal-modal" data-reveal>';
   echo form_open(base_url().'notifications/subscriber/add/'.$encodeduser.'',$rrs);
   ?>
      <div class="header">
      <span><?php echo lang('registerfornotification'); ?></span>
      </div>
      <div class="help"><?php echo lang('rhelp_addnotification'); ?></div>
      <p class="message"></p>
     <div>
      <?php
       $this->load->helper('shortcodes');
       $codes = notificationCodes();
       $typedropdown[''] = lang('rr_pleaseselect');
       foreach($codes as $k=>$v)
       {
         $typedropdown[''.$v['group'].''][$k] = lang(''.$v['desclang'].'');
       }
       echo form_fieldset();
       echo '<ul>';
       echo '<li>'. form_label(lang('whennotifyme'),'type');
       echo form_dropdown('type', $typedropdown,'','id="type" class="smallselect"'). '</li>';

       echo '<li>'. form_label(lang('rr_provider'),'sprovider');
       echo form_dropdown('sprovider', array(),'','id="sprovider"').'</li>';

       echo '<li>'.form_label(lang('rr_federation'),'sfederation');
       echo form_dropdown('sfederation', array(),'','id="sfederation"').'</li>';
 
       echo '<li>'.form_label(''.lang('rr_altemail').' ('.lang('rr_optional').')','semail');
       echo '<input type="text" id="semail" name="semail" value=""/></li>';
   
       echo '</ul>';
       echo form_fieldset_close();
     ?>
    </div>
      <div class="buttons">
      <div class="no"><?php echo lang('rr_cancel');?></div>
      <div class="yes"><?php echo lang('rr_add');?></div>
     </div>
   <?php
   echo form_close();
   echo' <a class="close-reveal-modal">&#215;</a>';
   echo '</div>';

