<div id="pagetitle"><?php echo lang('rr_requiredattributes'); ?></div>
<?php
$fed_link = anchor(base_url().'federations/manage/show/'.$fed_encoded,'<img src="'.base_url().'images/icons/arrow-in.png"/>');
$fed_link = anchor(base_url().'federations/manage/show/'.$fed_encoded,$fed_name);
?>
<div id="subtitle"><h3><?php echo lang('rr_federation').': '.$fed_link;?></h3></div>
<?php

if (!empty($message))
{
    echo '<span class="message">' . $message . '</span>';
}
if (!empty($error))
{
    echo '<span class="error">' . $error . '</span>';
}
?>
<?php
$attributes = array('class' => 'reqattraddform', 'id' => 'formver1');
$attributes2 = array('class' => 'span-16', 'id' => 'formver2');
$spid_hidden = array('fedid' => $fedid,'type'=>'FED');
$target = current_url();
if (count($add_attr_final) > 0)
{
?>
<button class="addbutton addicon showform"><?php echo lang('rr_addreqattr'); ?></button>
<button class="resetbutton minusicon hideform hidden"><?php echo lang('btn_hide');?></button></div>

<?php
    for ($i = 0; $i < $no_new_attr; $i++)
    {
        echo form_open(base_url(). "manage/attribute_requirement/fedsubmit", $attributes, $spid_hidden);
        echo form_fieldset(lang('rr_addreqattr'));
        echo '<ol>';
        echo '<li>';
        echo form_label(lang('rr_selectattr'), 'attribute');

        echo form_dropdown('attribute', $add_attr_final);
        echo '</li>';
        echo '<li>';
        echo form_label(lang('rr_selectreq'), 'requirement');
        echo form_dropdown('requirement', array('desired' => ''.lang('dropdesired').'', 'required' => ''.lang('droprequired').''));
        echo '</li>';
        echo '<li>';
        echo form_label(lang('rr_reqattrreason'), 'reason');
        echo form_textarea(array('name' => 'reason', 'cols' => 25, 'rows' => 5));
        echo '</li>';
        echo '</ol>';
        echo form_fieldset_close();
        echo '<div class="buttons">';
        echo '<button name="submit" type="submit" id="submit" value="Add" class="savebutton saveicon">'.lang('rr_add').'</button>';
	echo '</div>';
        echo form_close();
    }
}
echo '<hr class="span-20 clear"/>';
if (count($already_in_attr) > 0)
{
    echo '<h3>'.lang('modremreqs').'</h3>';
    foreach ($already_in_attr as $a)
    {
        $spid_hidden['attribute'] = $a['attr_id'];
        $spid_hidden['type'] = 'FED';
        echo form_open(base_url() . 'manage/attribute_requirement/fedsubmit', $attributes2, $spid_hidden);
        echo form_fieldset($a['name']);
        echo "<ol>";
        echo "<p><b>\"" . $a['fullname'] . "\":</b> " . $a['description'] . "<br />
			<b>SAML1:</b> " . $a['urn'] . "<br />
			<b>SAML2:</b> " . $a['oid'] . "</p>";
        echo '<li>';
        echo form_label(''.lang('curstreq').'', 'requirement');
        echo form_dropdown('requirement', array('desired' => ''.lang('dropdesired').'', 'required' => ''.lang('droprequired').''), $a['status']);
        echo '</li>';
        echo '<li></li>';
        echo '<li>';
        echo form_label(lang('reasonofreq'), 'reason');
        echo form_textarea(array('name' => 'reason', 'cols' => 25, 'rows' => 5, 'value' => $a['reason']));
        echo '</li>';
        echo '<ol>';
        echo '<div class="buttons">';
        echo '<button name="submit" type="submit" value="Remove" class="resetbutton deleteicon">'.lang('rr_remove').'</button> ';
        echo '<button name="submit" type="submit" value="Modify" class="savebutton saveicon">'.lang('rr_modify').'</button>';
        echo '</div>';
        echo form_fieldset_close();
        echo form_close();
    }
}
