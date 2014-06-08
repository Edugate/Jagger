
<div id="fedcategories" class="button-group">
<?php

$defaultSet = FALSE;
$catButtons = '';

if(count($categories)>0)
{
   foreach($categories as $v)
   {
       
       if($defaultSet)
       {
           $catButtons .= '<button type="button" class="btn fedcategory button small" title="'.$v['title'].'" value="'.base_url().'ajax/fedcat/'.$v['catid'].'">'.$v['name'].'</button> ';
       }
       elseif(!empty($v['default']))
       {
           $catButtons .= '<button type="button" class="btn fedcategory button activated active small" title="'.$v['title'].'" value="'.base_url().'ajax/fedcat/'.$v['catid'].'">'.$v['name'].'</button> ';
           $defaultSet = TRUE;
       }
       else
       {
           $catButtons .= '<button type="button" class="btn fedcategory button small" title="'.$v['title'].'" value="'.base_url().'ajax/fedcat/'.$v['catid'].'">'.$v['name'].'</button> ';

       }
   }

   if($defaultSet)
   {
     $allFedsBtn =  '<button type="button" class="btn fedcategory button small" title="All federations" value="'.base_url().'ajax/fedcat/" id="fedcategoryall">'.lang('rr_allfeds').'</button> ';
   }
    else
   {
     $allFedsBtn =  '<button type="button" class="btn fedcategory activated active button small" title="All federations" value="'.base_url().'ajax/fedcat/" id="fedcategoryall">'.lang('rr_allfeds').'</button> ';

   }
}
else
{
     $allFedsBtn =  '<button type="button" class="btn fedcategory activated button hidden small" title="All federations" value="'.base_url().'ajax/fedcat/" id="fedcategoryall">'.lang('rr_allfeds').'</button> ';


}



echo $allFedsBtn;
echo $catButtons;

?>
</div>


<div id="fedistpercati"></div>


<?php
echo '<table  id="detailsnosort" class="fedistpercat tablesorter drop-shadow lifted columns"> <thead> <tr>
<th>'.lang('rr_tbltitle_name').'</th><th>'.lang('fednameinmeta').'</th><th></th><th>'.lang('Description').'</th><th>#</th></tr> </thead>
<tbody> </tbody> </table> ';
?>
