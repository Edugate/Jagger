<?php

if (!empty($message)) {
    echo "<span class=\"notice\">" . $message . "</span>";
}
if (!empty($error)) {
    echo "<span class=\"alert\">" . $error . "</span>";
}
$attributes = array('class' => 'email', 'id' => 'formver2');
$attributes2 = array('class' => 'email', 'id' => 'formver2');
$spid_hidden = array('spid' => $spid, 'type' => 'SP');
$sp_link = anchor(base_url() . 'providers/provider_detail/sp/' . $spid, '<img src="' . base_url() . 'images/icons/block-share.png"/>');
$target = current_url();


echo "<div id=\"subtitle\">";
echo "<dl>";
echo "<dt>";
echo "Service Provider";
echo "<dt>";

echo "<dd>";
echo $sp_name . ' (' . $sp_entityid . ')' . $sp_link;
echo "</dd>";
echo "</dl>";
echo "</div>";
?>
<table id="details" style="border: 0px" >
    <caption>Add new required attribute</caption>
    <tbody><tr><td style="border: 0px;padding: 0px">
<?php

if (count($add_attr_final) > 0) {
    for ($i = 0; $i < $no_new_attr; $i++) {
        echo form_open(base_url() . "manage/attribute_requirement/submit", $attributes, $spid_hidden);
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
        echo form_textarea(array('name' => 'reason', 'cols' => 30, 'rows' => 5));
        echo "</li>";
        echo "</ol>";
        $tf = '';
        $tf .='<div class="buttons">';
        $tf .='<button type="submit" name="submit" id="submit" value="Add" class="button positive">
                  <span class="save">Add</span></button>';
        $tf .= '</div>';
        echo $tf;
        echo form_close();
    }
}
echo "</td></tr></table>";
if (count($already_in_attr) > 0) {

    echo "<table id=\"details\" style=\"border: 0px;\"><caption>Modify or remove existing requirement(s)</caption><theader><td>Name</td><td>Oid</td><td>reason</td><td>status</td></theader><tbody>";
    foreach ($already_in_attr as $a) {
        echo "<tr class=\"accordionButton\">";
        echo "<td>" . $a['fullname'] . "</td>";
        echo "<td>" . $a['oid'] . "</td>";
        echo "<td>" . $a['reason'] . "</td>";
        echo "<td>" . $a['status'] . "</td>";
        echo "</tr>";
        echo "<tr class=\"accordionContent\"><td colspan=\"4\">";

        $spid_hidden['attribute'] = $a['attr_id'];
        $spid_hidden['type'] = 'SP';
        echo form_open(base_url() . "manage/attribute_requirement/submit", $attributes2, $spid_hidden);
        echo form_fieldset($a['name']);
        echo "<ol>";
        echo "<div><b>\"" . $a['fullname'] . "\":</b> " . $a['description'] . "<br />
			<b>SAML1:</b> " . $a['urn'] . "<br />
			<b>SAML2:</b> " . $a['oid'] . "</div>";
        echo "<li>";
        echo form_label('Current type of requirement', 'requirement');
        echo form_dropdown('requirement', array('desired' => 'desired', 'required' => 'required'), $a['status']);
        echo "</li>";
        echo "<li>";
        echo form_label('The reason of requirement', 'reason');
        echo form_textarea(array('name' => 'reason', 'cols' => 30, 'rows' => 5, 'value' => $a['reason']));
        echo "</li>";
        echo "<ol>";
        echo form_fieldset_close();
        $tf = '';
        $tf .='<div class="buttons">';
        $tf .='<button type="submit" name="submit" value="Remove" class="button negative">
                  <span class="reset">Remove</span></button>';
        $tf .='<button type="submit" name="submit" value="Modify" class="button positive">
                  <span class="save">Modify</span></button>';
        $tf .= '</div>';
        echo $tf;
        echo form_close();
        echo "</td></tr>";
    }
    echo "</tbody></table>";
}
?>
