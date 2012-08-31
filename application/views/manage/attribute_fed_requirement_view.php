<?php
$fed_link = anchor(base_url().'federations/manage/show/'.$fed_encoded,'<img src="'.base_url().'images/icons/arrow-in.png"/>');

echo  "<div id=\"subtitle\">";
echo "<dl>";
echo "<dt>";
echo "Federation";
echo "<dt>";

echo "<dd>";
echo $fed_name.' '.$fed_link;
echo "</dd>";
echo "</dl>";
echo "</div>";
if (!empty($message))
{
    echo "<span class=\"message\">" . $message . "</span>";
}
if (!empty($error))
{
    echo "<span class=\"error\">" . $error . "</span>";
}
$attributes = array('class' => 'span-16', 'id' => 'formver1');
$attributes2 = array('class' => 'span-16', 'id' => 'formver2');
$spid_hidden = array('fedid' => $fedid,'type'=>'FED');
$target = current_url();
if (count($add_attr_final) > 0)
{
    for ($i = 0; $i < $no_new_attr; $i++)
    {
        echo form_open(base_url(). "manage/attribute_requirement/fedsubmit", $attributes, $spid_hidden);
        echo form_fieldset('Add new attribute requirement');
        echo "<ol>";
        echo "<li>";
        echo form_label('Select attribute', 'attribute');

        echo form_dropdown('attribute', $add_attr_final);
        echo "</li>";
        echo "<li>";
        echo form_label('Select requirement', 'requirement');
        echo form_dropdown('requirement', array('desired' => 'desired', 'required' => 'required'));
        echo "</li>";
        echo "<li>";
        echo form_label('The reason of requirement', 'reason');
        echo form_textarea(array('name' => 'reason', 'cols' => 5, 'rows' => 5));
        echo "</li>";
        echo "</ol>";
        echo form_fieldset_close();
        echo '<div class="buttons">';
        echo '<button name="submit" type="submit" id="submit" value="Add" class="btn positive"><span class="save">Add</span></button>';
	echo '</div>';
        echo form_close();
    }
}
//echo "</td></tr>";

//echo '<table id="details" class="tablesorter"><caption>Attributes requirements</caption><tbody>';
//echo '<span class="span-24 clear-fix">';
echo '<hr class="span-20 clear"/>';
//echo '</span>';
if (count($already_in_attr) > 0)
{
 //   echo "<tr><td>";
    echo "<h3>Modify or remove existing requirement(s)</h3>";
    foreach ($already_in_attr as $a)
    {
        $spid_hidden['attribute'] = $a['attr_id'];
        $spid_hidden['type'] = 'FED';
        echo form_open(base_url() . "manage/attribute_requirement/fedsubmit", $attributes2, $spid_hidden);
        echo form_fieldset($a['name']);
        echo "<ol>";
        echo "<p><b>\"" . $a['fullname'] . "\":</b> " . $a['description'] . "<br />
			<b>SAML1:</b> " . $a['urn'] . "<br />
			<b>SAML2:</b> " . $a['oid'] . "</p>";
        echo "<li>";
        echo form_label('Current state of requirement', 'requirement');
        echo form_dropdown('requirement', array('desired' => 'desired', 'required' => 'required'), $a['status']);
        echo "</li>";
        echo "<li></li>";
        echo "<li>";
        echo form_label('The reason of requirement', 'reason');
        echo form_textarea(array('name' => 'reason', 'cols' => 5, 'rows' => 5, 'value' => $a['reason']));
        echo "</li>";
        echo "<ol>";
        echo '<div class="buttons">';
        echo '<button name="submit" type="submit" value="Remove" class="btn negative"><span class="cancel">Remove</span></button>';
        echo '<button name="submit" type="submit" value="Modify" class="btn positive"><span class="save">Modify</span></button>';
        echo '</div>';
        echo form_fieldset_close();
        echo form_close();
    }
   // echo "</td></tr>";
}
