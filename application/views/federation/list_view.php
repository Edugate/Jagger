
<div id="fedcategories" class="">
<?php

$defaultSet = FALSE;
$catButtons = '';

if(count($categories)>0)
{
   echo '<dl class="sub-nav"> <dt>'.lang('rr_category').':</dt>';
   foreach($categories as $v)
   {
       
       if($defaultSet)
       {
           echo  '<dd><a href="'.base_url().'ajax/fedcat/'.$v['catid'].'" class="fedcategory">'.$v['name'].'</a></dd>';
       }
       elseif(!empty($v['default']))
       {
           echo  '<dd class="active"><a href="'.base_url().'ajax/fedcat/'.$v['catid'].'" class="fedcategory">'.$v['name'].'</a></dd>';
           $defaultSet = TRUE;
       }
       else
       {
           echo  '<dd><a href="'.base_url().'ajax/fedcat/'.$v['catid'].'" class="fedcategory">'.$v['name'].'</a></dd>';

       }
   }

   if($defaultSet)
   {
           echo  '<dd><a href="'.base_url().'ajax/fedcat/" id="fedcategoryall" class="fedcategory">'.lang('rr_allfeds').'</a></dd>';
   }
    else
   {
           echo  '<dd class="active"><a href="'.base_url().'ajax/fedcat/" id="fedcategoryall" class="fedcategory">'.lang('rr_allfeds').'</a></dd>';

   }
   echo '</dl>';
}



?>
</div>


<div id="fedistpercati"></div>


<?php
echo '<table  id="detailsnosort" class="fedistpercat tablesorter drop-shadow lifted columns"> <thead> <tr>
<th>'.lang('rr_tbltitle_name').'</th><th>'.lang('fednameinmeta').'</th><th></th><th>'.lang('Description').'</th><th>#</th></tr> </thead>
<tbody> </tbody> </table> ';
?>
