<?php
echo '<div id="pagetitle">'. lang('title_usersubscriptions').'</div>';
echo '<div id="subtitle"><h3>'.lang('rr_user').': '.htmlentities($subscriber['username']).'</h3><h4>'.htmlentities($subscriber['fullname']).' '.mailto($subscriber['email']).'</h4></div>';


if(!empty($warnmessage))
{
    echo '<div class="alert">'.$warnmessage.'</div>';
 
}
?>
<div style="float: right; witdth: 99%;"><button id="registernotification" class="addbutton addicon" type="button"><?php echo lang('rr_add');?></button></div>
<div style="clear: both;"></div>
<?php
  if(count($rows)>1)
  {
   $tmpl = array('table_open' => '<table id="detailsnosort" class="zebra">');
   $this->table->set_template($tmpl);
   echo $this->table->generate($rows); 
   $this->table->clear();
  }

/**
 * update form
 */
   $rrs = array('id'=>'notificationupdateform','style'=>'display: none');

   $this->load->helper('form');
   echo form_open(base_url().'notification/subcriber/updatestatus/',$rrs);
   echo form_input(array('name'=>'noteid','id'=>'noteid','type'=>'hidden','value'=>''));
   ?>
      <div class="header">
      <span><?php echo 'update status'; ?></span>
      </div>
      <div></div>
      <p class="message"></p>
     <div>
      <?php
       echo form_dropdown('status', $statusdropdown);
     ?>
    </div>
      <div class="buttons">
      <div class="yes"><?php echo lang('btnupdate');?></div>
      <div class="no simplemodal-close"><?php echo lang('rr_cancel');?></div>
     </div>
   <?php
   echo form_close();

/**
 * add form
 */
   $rrs = array('id'=>'notificationaddform');

   $this->load->helper('form');
   echo form_open(base_url().'notification/subcriber/add/',$rrs);
   //echo form_input(array('name'=>'noteid','id'=>'noteid','type'=>'hidden','value'=>''));
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
         $typedropdown[''.$k.''] = lang(''.$v['desclang'].'');
       }
       echo form_fieldset();
       echo '<ul>';
       echo '<li>'. form_label(lang('whennotifyme'),'type');
       echo form_dropdown('type', $typedropdown,'','id="type"'). '</li>';

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
      <div class="yes"><?php echo lang('rr_add');?></div>
      <div class="no simplemodal-close"><?php echo lang('rr_cancel');?></div>
     </div>
   <?php
   echo form_close();


