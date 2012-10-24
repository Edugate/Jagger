<?php
if(!empty($error_message))
{
    echo "<span class=\"alert\">".$error_message."</span>"; 
}
if (!empty($edit_form))
{
    if (!empty($subtitle))
    {
        $link_policy = anchor(base_url() . "/manage/attribute_policy/globals/" . $idp_id, '<img src="' . base_url() . 'images/icons/application-browser.png" />');
        $link_idp = anchor(base_url() . "providers/provider_detail/idp/" . $idp_id, '<img src="' . base_url() . 'images/icons/home.png" />');
        echo '<br />';
        echo '<h3>' .$link_policy. $subtitle . '</h3>';
        echo "<h3>Provider: " .$idp_name . $link_idp . "</h3>";
		if($type == 'sp')
		{
        	$link_sp = anchor(base_url() . "providers/provider_detail/sp/" . $requester_id, '<img src="' . base_url() . 'images/icons/application-browser.png" />');
			
        	echo "<h3>Requester: " .$sp_name . $link_sp . "</h3>";
		}
		if($type == 'fed')
		{
			$encoded_fedname = $fed_url;
        	$link_fed = anchor(base_url() . "federations/manage/show/" . $encoded_fedname, '<img src="' . base_url() . 'images/icons/application-browser.png" />');
			
        	echo "<h3>Federation requester: " .$fed_name . $link_fed. "</h3>";
		
		}
    }
    echo $edit_form;
}
