<?php
$idp_link = anchor(base_url()."providers/provider_detail/idp/".$idp_id,'<img src="' . base_url() . 'images/icons/home.png" />');
echo "<div id=\"subtitle\">";
echo "<dl>";
echo "<dt>Provider</dt> <dd>".$idp_name."</b> (".$idp_entityid.")".$idp_link."</dd>";

echo "<dd>Attribute Release Policies<a href=\"".base_url()."manage/attribute_policy/globals/".$idp_id."\"><img src=\"".base_url()."images/icons/arrow.png\" /></a></dd>";


echo "</dl>";
echo "</div>";
?>


<?php
if(!empty($form_attributes))
{
	echo $form_attributes;
}
