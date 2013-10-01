<div id="pagetitle"><?php echo lang('statdefdetail');?></div>
<div id="subtitle">
 <h3><?php echo anchor(base_url().'providers/detail/show/'.$providerid, $providername ) ;?></h3>
 <h4><?php echo $providerentity;?></h4>
 <h5><?php echo anchor(base_url().'manage/statdefs/show/'.$providerid,lang('statdeflist')) ;?></h5>
</div>
<?php
    $tmpl = array ( 'table_open'  => '<table id="detailsnosort" class="zebra">' );
    $this->table->set_template($tmpl);
    echo $this->table->generate($details);
    $this->table->clear();

?>
<div id="statisticdiag"></div>
<div style="height: 50px;"></div>
