<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');



if (!empty($error_message))
{
    echo '<div class="alert">' . $error_message . '</div>';
}

if (!empty($excluded) && is_array($excluded) && count($excluded) > 0)
{
    if (!empty($has_write_access))
    {
        $editlink = '<span class="lbl lbl-disabled"><a href="' . base_url() . 'manage/arpsexcl/idp/' . $idpid . '">' . lang('rr_editarpexc') . '</a></span>';
    }
    else
    {
        $editlink = '';
    }

    echo '<div id="excarpslist"><b>' . lang('rr_arpexclist_title') . '</b> ' . $editlink;
    echo '<ol>';
    foreach ($excluded as $v)
    {
        echo '<li>' . $v . '</li>';
    }
    echo '</ol></div>';
}
?>
<div class="row">
<div class="small-12 columns">
    <div class="medium-9 columns"></div>
    <div class="medium-3 columns">
        <label class="hide-for-small-only"><input id="tablesearchinput" type="text" placeholder="<?php echo lang('rr_filter');?>" /></label>
    </div>
</div>
</div>
<?php

echo '<div id="matrixloader" data-jagger-link="' . base_url() . 'reports/idpmatrix/getarpdata/' . $idpid . '" data-jagger-providerdetails="'.base_url().'providers/detail/show"  class="row hidden"></div>';
echo '<div id="idpmatrixdiv" class="row" style="margin-top: 20px"></div>';




$rrs = array('id' => 'idpmatrixform', 'style' => 'display: none');

echo form_open(base_url() . 'manage/attribute_policyajax/submit_sp/' . $idpid, $rrs);
echo form_input(array('name' => 'attribute', 'id' => 'attribute', 'type' => 'hidden', 'value' => ''));
echo form_input(array('name' => 'idpid', 'id' => 'idpid', 'type' => 'hidden', 'value' => '' . $idpid . ''));
echo form_input(array('name' => 'requester', 'id' => 'requester', 'type' => 'hidden', 'value' => ''));
?>
<div class="small-12 columns">
    <?php echo lang('confirmupdpolicy'); ?>
</div>
<div class="attrflow small-12 columns"></div>
<p class="message"><?php echo lang('rr_tbltitle_requester') . ': '; ?><span class="mrequester"></span><br /><?php echo lang('attrname') . ': '; ?><span class="mattribute"></span></p>
<div>
    <?php
    $dropdown = $this->config->item('policy_dropdown');
    $dropdown = array_merge(array('' => lang('rr_select')), $dropdown);
    echo form_dropdown('policy', $dropdown, set_value('policy'));
    ?>
</div>
<div class="buttons small-12 columns">
    <div class="yes button"><?php echo lang('btnupdate'); ?></div>
    <div class="no simplemodal-close button"><?php echo lang('rr_cancel'); ?></div>
</div>
<?php
echo form_close();

echo '
  <div id="policyupdater" class="reveal-modal small" data-reveal jagger-data-link="' . base_url() . 'manage/attribute_policyajax/getattrpath/' . $idpid . '">
  <h2>' . lang('confirmupdpolicy') . '</h2>
      
      <p class="message">' . lang('rr_tbltitle_requester') . ':  <span class="mrequester"></span><br />' . lang('attrname') . ': <span class="mattribute"></span></p>
     <div>
     ';
echo '<div class="attrflow small-12 columns"></div>';
echo form_open(base_url() . 'manage/attribute_policyajax/submit_sp/' . $idpid);
echo form_input(array('name' => 'attribute', 'type' => 'hidden', 'value' => ''));
echo form_input(array('name' => 'idpid', 'type' => 'hidden', 'value' => '' . $idpid . ''));
echo form_input(array('name' => 'requester', 'type' => 'hidden', 'value' => ''));
$dropdown = $this->config->item('policy_dropdown');
echo '<div class="small-12 colums">';
$dropdown = array_merge(array('' => lang('rr_select')), $dropdown);
echo '<div class="medium-3 columns medium-text-right"><label for="policy" class="inline" >Policy</label></div>';
echo '<div class="medium-9 columns">'.form_dropdown('policy', $dropdown, '').'</div>';
echo '</div>';
echo '<p><div class="buttons small-12 columns small-text-right">
    <div class="small-6 columns">
    <div class="no close-reveal-modal button">' . lang('rr_cancel') . '</div>
        </div>
    <div class="small-6 columns">
    <div class="yes button">' . lang('btnupdate') . '</div>
        </div>
        
</div></p>';
echo '    
</form>
  
  <a class="close-reveal-modal">&#215;</a>
</div>';
