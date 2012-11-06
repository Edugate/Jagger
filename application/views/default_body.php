<div id="subtitle"> 
</div>
<div>
<?php
$entdelurl = base_url().'ajax/delbookentity/';
$image = base_url().'images/icons/star--minus.png';
 if(!empty($text_body))
{
print_r($text_body);

}


?>

<table>
<tr><td>
<?php
echo '<ul>';
foreach($idps as $k=>$i)
{
 echo '<li>'.$i.'<a href="'.$entdelurl.$k.'" class="bookentity"><img src="'.$image.'"/></a></li>';

}
echo '</ul>';
?>

</td>
<td>
<?php
echo '<ul>';
foreach($sps as $k=>$i)
{
 echo '<li>'.$i.'<a href="'.$entdelurl.$k.'" class="bookentity"><img src="'.$image.'"/></a></li>';

}
echo '</ul>';
?>
</td></tr>

</table>


</div>

