
<?php
$form = '<form id="filter-form"><input name="filter" id="filter" value="" type="text" placeholder="'.lang('rr_filter').'"></form>';
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
//echo $form;

$prefurl = base_url().'providers/sp_list/';
?>
<div class="row">
<div class="small-10 medium-9 large-9 columns">
<?php
if($typesps === 'local')
{
?>
<button type="button" class="btn typelist small" onclick="window.location.href='<?php echo $prefurl; ?>show/all'" ><?php echo lang('allprov');?></button>
<button type="button" class="btn typelist small" onclick="window.location.href='<?php echo $prefurl; ?>show/ext'" ><?php echo lang('extprov');?></button>
<button type="button" class="btn tchosen small" disabled="disabled"><?php echo lang('localprov');?></button>
<?php
}
elseif($typesps === 'external')
{
?>
<button type="button" class="btn typelist small" onclick="window.location.href='<?php echo $prefurl; ?>show/all'" ><?php echo lang('allprov');?></button>
<button type="button" class="btn tchosen small" disabled="disabled"><?php echo lang('extprov');?></button>
<button type="button" class="btn typelist small" onclick="window.location.href='<?php echo $prefurl; ?>show'" ><?php echo lang('localprov');?></button>

<?php
}
elseif($typesps === 'all')
{
?>
<button type="button" class="btn tchosen small" disabled="disabled"><?php echo lang('allprov');?></button>
<button type="button" class="btn typelist small" onclick="window.location.href='<?php echo $prefurl; ?>show/ext'" ><?php echo lang('extprov');?></button>
<button type="button" class="btn typelist small" onclick="window.location.href='<?php echo $prefurl; ?>show'" ><?php echo lang('localprov');?></button>

<?php
}
echo '</div>';
echo '<div class="small-2 medium-3 large-3 columns">';
echo $form;
echo '</div>';
?>
</div>

<?php

$tmpl = array('table_open' => '<table  id="details" class="zebra splist filterlist columns">');

$this->table->set_template($tmpl);
$this->table->set_heading(lang('e_orgname') .'/entityID','#',lang('tbl_title_regdate'), lang('tbl_title_helpurl'));
echo $this->table->generate($sprows);
$this->table->clear();
?>
