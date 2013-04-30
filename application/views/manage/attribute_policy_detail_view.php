		
<?php
if(!empty($error_message))
{
    echo '<span class="alert">'.$error_message.'</span>'; 
}
if (!empty($edit_form))
{
    if (!empty($subtitle))
    {
        $link_policy = anchor(base_url() . "/manage/attribute_policy/globals/" . $idp_id, 'Back to defeault policy list');
        $link_idp = anchor(base_url() . "providers/detail/show/" . $idp_id, $idp_name);
        echo '<br />';
        echo '<div id="pagetitle">' . $subtitle . ' per attribute</div>';
        echo '<div id="subtitle"><h3>Provider: ' . $link_idp . '</h3><h4>'.$provider_entity.'</h4>'.$link_policy.'</div>';
		if($type == 'sp')
		{
        	$link_sp = anchor(base_url() . "providers/detail/show/" . $requester_id, '<img src="' . base_url() . 'images/icons/application-browser.png" />');
			
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
