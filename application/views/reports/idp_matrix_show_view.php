<?php 
if (!defined('BASEPATH'))
    exit('No direct script access allowed');



if(!empty($error_message))
{
  echo '<div class="alert">'.$error_message.'</div>';
}

if(!empty($excluded) && is_array($excluded) && count($excluded)>0)
{
   if(!empty($has_write_access))
   {
        $editlink = '<span class="lbl lbl-disabled"><a href="'.base_url().'manage/arpsexcl/idp/'.$idpid.'">'.lang('rr_editarpexc').'</a></span>';
   }
   else
   {
        $editlink = '';
   }

   echo '<div id="excarpslist"><b>'.lang('rr_arpexclist_title').'</b> '.$editlink;
   echo '<ol>';
   foreach($excluded as $v)
   {
       echo '<li>'.$v.'</li>';
   }
   echo '</ol></div>';
}

if(!empty($result))
{
   $tmpl = array('table_open' => '<table  id="idpmatrix" class="zebra">');
   $this->table->set_template($tmpl);
   $this->table->set_empty('');
   echo   $this->table->generate($result);
   $this->table->clear();

   $arpinherit = $this->config->item('arpbyinherit');
   if(!empty($arpinherit))
   {
   $rrs = array('id'=>'idpmatrixform','style'=>'display: none');

   echo form_open(base_url().'manage/attribute_policyajax/submit_sp/'.$idpid,$rrs);
   echo form_input(array('name'=>'attribute','id'=>'attribute','type'=>'hidden','value'=>''));
   echo form_input(array('name'=>'idpid','id'=>'idpid','type'=>'hidden','value'=>''.$idpid.''));
   echo form_input(array('name'=>'requester','id'=>'requester','type'=>'hidden','value'=>''));
   ?>
      <div class="small-12 columns">
      <?php echo lang('confirmupdpolicy');?>
      </div>
      <div class="attrflow small-12 columns"></div>
      <p class="message"><?php echo lang('rr_tbltitle_requester').': ' ;?><span class="mrequester"></span><br /><?php echo lang('attrname').': ';?><span class="mattribute"></span></p>
     <div>
      <?php
       $dropdown = $this->config->item('policy_dropdown');
       $dropdown = array_merge( array(''=>lang('rr_select')), $dropdown);
       echo form_dropdown('policy', $dropdown,set_value('policy'));
     ?>
    </div>
      <div class="buttons small-12 columns">
      <div class="yes button"><?php echo lang('btnupdate');?></div>
      <div class="no simplemodal-close button"><?php echo lang('rr_cancel');?></div>
     </div>
   <?php
   echo form_close();
   }
   else
   {
  ?>
    <div id="idpmatrixform"><div class="header"><span>Problem</span></div>
  <p class="message">Function inactive</p>
  <div class="buttons">
  <div class="no simplemodal-close"><?php echo lang('rr_close');?></div>
  </div>
  </div>
<?php
   }
}
