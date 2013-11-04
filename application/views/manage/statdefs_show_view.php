<div id="pagetitle"><?php echo lang('statsmngmt');?></div>
<div id="subtitle"><h3><?php echo anchor(base_url().'providers/detail/show/'.$providerid, $providername ) ;?></h3><h4><?php echo $providerentity;?></h4></div>
<div style="float: right; witdth: 99%;"><a href="<?php echo base_url().'manage/statdefs/newstatdef/'.$providerid.''; ?>" class="addbutton addicon">add</a></div>
<div style="clear: both;"></div>
<div id="statdefs" style="width: 100%">
<?php
if(!empty($existingStatDefs))
{
    $tmpl = array ( 'table_open'  => '<table id="details" class="zebra">' );
    $this->table->set_template($tmpl);
    $staimg = base_url().'images/stats_bars.png';
    $refreshimg = base_url().'images/icons/arrow-circle-315.png';
    $infoimg = base_url().'images/icons/information.png';
    foreach($existingStatDefs as $v)
    {
       if(empty($v['alert']))
       {
           $r = array(anchor(base_url().'manage/statdefs/show/'.$providerid.'/'.$v['id'].'',$v['title']).' <div style="display: inline; text-align: left; width: 99%; margin-right: 0px;margin-left: auto;"><a href="'.base_url().'manage/statdefs/statdefedit/'.$providerid.'/'.$v['id'].'"><img src="'.base_url().'images/icons/ice--pencil.png"></a>&nbsp;&nbsp;<a class="lateststat" href="'.base_url().'manage/statistics/latest/'.$v['id'].'"><img src="'.$staimg.'"/></a> <a class="downloadstat" href="'.base_url().'manage/statdefs/download/'.$v['id'].'"><img src="'.$refreshimg.'"/></a></div>',''.htmlentities($v['desc']).'');
       }
       else
       {
           $r = array(anchor(base_url().'manage/statdefs/show/'.$providerid.'/'.$v['id'].'',$v['title']).' <span style="float: right;" class="alert">'.lang('rerror_nopredefinedstat').'</span>',''.htmlentities($v['desc']).'');

       }
       $this->table->add_row($r);
    }
    echo $this->table->generate();
   $this->table->clear();

}
else
{
     echo '<div class="alert" style="width: 100%; text-align: center;">'.lang('nostatsdefsfound').' </div>';
}
?>
</div>
<div id="statisticdiag"></div>
