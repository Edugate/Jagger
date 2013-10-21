<div id="pagetitle"><?php echo lang('statdefdetail');?></div>
<div id="subtitle">
 <h3><?php echo anchor(base_url().'providers/detail/show/'.$providerid, $providername ) ;?></h3>
 <h4><?php echo $providerentity;?></h4>
 <h5><?php echo anchor(base_url().'manage/statdefs/show/'.$providerid,lang('statdeflist')) ;?></h5>

<?php

if(!empty($defid))
{
$formAttrs = array('id'=>'rmstatdef','class'=>'ajform');
$hidden = array('prvid'=>''.$providerid.'','defid'=>''.$defid.'');
echo form_open(''.base_url().'manage/statdefs/remove/'.$defid.'',$formAttrs,$hidden);
?>

<button type="submit" value="remove" name="remove" class="btn negative" >remove</button>
<?php
echo form_close();
}
?>
</div>
<?php
    $tmpl = array ( 'table_open'  => '<table id="detailsnosort" class="zebra">' );
    $this->table->set_template($tmpl);
    echo $this->table->generate($details);
    $this->table->clear();

?>
<div id="statisticdiag"></div>
<div style="height: 50px;"></div>

<div id="#statdefremoved" style="display: none">
                        <div class='header'><span>Confirm</span></div>
                        <div class='message'></div>
                        <div class='buttons'>
                                <div class='no simplemodal-close'>No</div><div class='yes'>Yes</div>
                        </div>
</div>

