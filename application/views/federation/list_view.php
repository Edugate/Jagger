<div id="subtitle"><h3><?php echo lang('rr_federation_list');?></h3></div>

<div id="fedcategories">
<?php
if(count($categories)>0)
{
   foreach($categories as $v)
   {

       echo '<button type="button" class="btn fedcategory" title="'.$v['title'].'" value="'.base_url().'ajax/fedcat/'.$v['catid'].'">'.$v['name'].'</button> ';
   }
   echo '<button type="button" class="btn fedcategory" title="All federations" value="'.base_url().'ajax/fedcat/" id="fedcategoryall">All Federations</button> ';
}

?>
</div>


<div id="fedistpercati"></div>


<?php
$tmpl = array('table_open' => '<table  id="details" class="fedistpercat tablesorter drop-shadow lifted">');
$this->table->set_template($tmpl);
$this->table->set_heading(lang('rr_tbltitle_name'),lang('fedurn'),'',lang('Description'),'#');
//echo $this->table->generate($fedlist);
echo $this->table->generate();
$this->table->clear();
?>
