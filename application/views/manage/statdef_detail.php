<div class="small-12 columns text-right">
<?php

if(!empty($defid))
{
echo '<div style="">';
$formAttrs = array('id'=>'rmstatdef','class'=>'ajform');
$hidden = array('prvid'=>''.$providerid.'','defid'=>''.$defid.'');
echo form_open(''.base_url().'manage/statdefs/remove/'.$defid.'',$formAttrs,$hidden);
?>
<button type="submit" value="remove" name="remove" class="resetbutton reseticon small alert" ><?php echo lang('rr_remove');?></button>
<?php
echo '<a href="'.base_url().'manage/statdefs/statdefedit/'.$providerid.'/'.$defid.'" class="button editbutton editicon small">'.lang('rr_edit').'</a>';
echo form_close();
echo '</div>';
}
?>
</div>
<?php
    $tmpl = array ( 'table_open'  => '<table id="detailsnosort" class="zebra small-12 columns">' );
    $this->table->set_template($tmpl);
    echo $this->table->generate($details);
    $this->table->clear();

?>
<div id="statisticdiag"></div>
<div style="height: 50px;"></div>


<?php
echo confirmDialog(''.lang('title_confirm').'', ''.sprintf(lang('douwanttoremove'),lang('statdefinition')).'', ''.lang('rr_yes').'', ''.lang('rr_no').'');
echo resultDialog(''.lang('title_result').'',''.lang('rr_removed').'! '.lang('gobacktothelist').'',''.lang('rr_close').'');
