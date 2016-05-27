
<div id="fedcategories" class="">
<?php

$defaultSet = FALSE;
$catButtons = '';

if(count($categories)>0)
{
   echo '<ul class="menu">';
   foreach($categories as $v)
   {
       
       if($defaultSet)
       {
           echo  '<li><a href="'.base_url().'ajax/fedcat/'.$v['catid'].'" class="fedcategory">'.$v['name'].'</a></li>';
       }
       elseif(!empty($v['default']))
       {
           echo  '<li class="active"><a href="'.base_url().'ajax/fedcat/'.$v['catid'].'" class="fedcategory">'.$v['name'].'</a></li>';
           $defaultSet = TRUE;
       }
       else
       {
           echo  '<li><a href="'.base_url().'ajax/fedcat/'.$v['catid'].'" class="fedcategory">'.$v['name'].'</a></li>';

       }
   }

   if($defaultSet)
   {
           echo  '<li><a href="'.base_url().'ajax/fedcat/" id="fedcategoryall" class="fedcategory">'.lang('rr_allfeds').'</a></li>';
   }
    else
   {
           echo  '<li class="active"><a href="'.base_url().'ajax/fedcat/" id="fedcategoryall" class="fedcategory">'.lang('rr_allfeds').'</a></li>';

   }
   echo '</ul>';
}
else
{
           echo  '<li class="hidden active"><a href="'.base_url().'ajax/fedcat/" id="fedcategoryall" class="fedcategory">'.lang('rr_allfeds').'</a></li>';


}



?>
</div>


<div id="fedistpercati"></div>


<?php
echo '<table  id="detailsnosort" class="fedistpercat tablesorter drop-shadow lifted columns"> <thead> <tr>
<th>'.lang('rr_tbltitle_name').'</th><th>'.lang('fednameinmeta').'</th><th></th><th>'.lang('Description').'</th><th>#</th></tr> </thead>
<tbody> </tbody> </table> ';
?>
