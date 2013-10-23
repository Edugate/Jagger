<div id="subtitle"><h3><?php echo lang('rr_federation_list');?></h3></div>

<div id="fedcategories">
<?php

$defaultSet = FALSE;
$catButtons = '';

if(count($categories)>0)
{
   foreach($categories as $v)
   {
       
       if($defaultSet)
       {
           $catButtons .= '<button type="button" class="btn fedcategory" title="'.$v['title'].'" value="'.base_url().'ajax/fedcat/'.$v['catid'].'">'.$v['name'].'</button> ';
       }
       elseif(!empty($v['default']))
       {
           $catButtons .= '<button type="button" class="btn fedcategory activated" title="'.$v['title'].'" value="'.base_url().'ajax/fedcat/'.$v['catid'].'">'.$v['name'].'</button> ';
           $defaultSet = TRUE;
       }
       else
       {
           $catButtons .= '<button type="button" class="btn fedcategory" title="'.$v['title'].'" value="'.base_url().'ajax/fedcat/'.$v['catid'].'">'.$v['name'].'</button> ';

       }
   }
}
if($defaultSet)
{
   $allFedsBtn =  '<button type="button" class="btn fedcategory" title="All federations" value="'.base_url().'ajax/fedcat/" id="fedcategoryall">'.lang('rr_allfeds').'</button> ';
}
else
{
   $allFedsBtn =  '<button type="button" class="btn fedcategory activated" title="All federations" value="'.base_url().'ajax/fedcat/" id="fedcategoryall">'.lang('rr_allfeds').'</button> ';

}

echo $allFedsBtn;
echo $catButtons;

?>
</div>


<div id="fedistpercati"></div>


<?php
echo '<table  id="detailsnosort" class="fedistpercat tablesorter drop-shadow lifted"> <thead> <tr>
<th>'.lang('rr_tbltitle_name').'</th><th>'.lang('fedurn').'</th><th></th><th>'.lang('Description').'</th><th>#</th></tr> </thead>
<tbody> </tbody> </table> ';
?>
