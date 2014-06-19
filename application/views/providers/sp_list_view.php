<?php
$allactive = '';
$extactive = '';
$localactive = '';
$form = '<form id="filter-form"><input name="filter" id="filter" value="" type="text" placeholder="'.lang('rr_search').'"></form>';
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
   $localactive = 'active';
?>
<?php
}
elseif($typesps === 'external')
{
   $extactive = 'active';
?>

<?php
}
elseif($typesps === 'all')
{
   $allactive = 'active';
?>

<?php
}
echo '<dl class="sub-nav"> <dt>'.lang('rr_filter').':</dt> <dd class="'.$allactive.'"><a href="'.$prefurl.'show/all">'.lang('allprov').'</a></dd> <dd class="'.$extactive.'"><a href="'.$prefurl.'show/ext">'. lang('extprov').'</a></dd> <dd class="'.$localactive.'"><a href="'.$prefurl.'show">'.lang('localprov').'</a></dd> </dl>';

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
