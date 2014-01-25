<?php
echo '<div id="pagetitle">'. lang('title_usersubscriptions').'</div>';
echo '<div id="subtitle"><h3>'.lang('rr_user').': '.htmlentities($subscriber['username']).'</h3><h4>'.htmlentities($subscriber['fullname']).' '.mailto($subscriber['email']).'</h4></div>';


if(!empty($warnmessage))
{
    echo '<div class="alert">'.$warnmessage.'</div>';
 
}
?>
<?php
   $tmpl = array('table_open' => '<table id="detailsnosort" class="zebra">');
   $this->table->set_template($tmpl);
   echo $this->table->generate($rows); 
   $this->table->clear();
?>
<div id="subscrlist"></div>
<?php
   $rrs = array('id'=>'notificationupdateform','style'=>'display: none');

   $this->load->helper('form');
   echo form_open(base_url().'notification/subcriber/updatestatus/',$rrs);
   echo form_input(array('name'=>'noteid','id'=>'noteid','type'=>'hidden','value'=>''));
   ?>
      <div class="header">
      <span><?php echo 'update status'; ?></span>
      </div>
      <div class="attrflow"></div>
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
