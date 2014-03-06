<div id="pagetitle">Attribute Requirements</div>
<?php
if (!empty($message))
{
    echo '<span class="notice">' . $message . '</span>';
}
if (!empty($error))
{
    echo '<span class="alert">' . $error . '</span>';
}
$attributes = array('class' => 'email', 'id' => 'formver2');
$attributes2 = array('class' => 'email', 'id' => 'formver2');
$spid_hidden = array('spid' => $spid, 'type' => 'SP');
$target = current_url();
?>

<div id="subtitle">
<h3><?php echo lang('serviceprovider').': <a href="'.base_url().'providers/detail/show/'.$spid.'">'.$sp_name.'</a>';?></h3>
<h4><?php echo $sp_entityid ;?></h4>
</div>

<div>
<button class="addbutton addicon showform"><?php echo lang('rr_addreqattr'); ?></button>
<button class="resetbutton deleteicon hideform hidden"><?php echo lang('btn_hide') ;?></button></div>

<table id="details" class="reqattraddform hidden">
    <caption><?php echo lang('rr_addreqattr');?></caption>
    <tbody><tr><td style="border: 0px;padding: 0px">
                <?php
                if (count($add_attr_final) > 0)
                {
                    for ($i = 0; $i < $no_new_attr; $i++)
                    {
                        echo form_open(base_url() . "manage/attribute_requirement/submit", $attributes, $spid_hidden);
                        echo '<ol><li>';
                        echo form_label(lang('rr_selectattr'), 'attribute');

                        echo form_dropdown('attribute', $add_attr_final,set_value('attribute'));
                        echo '</li><li>';
                        echo form_label(lang('rr_selectreq'), 'requirement');
                        echo form_dropdown('requirement', array('desired' => ''.lang('dropdesired').'', 'required' => ''.lang('droprequired').''),set_value('requirement'));
                        echo '</li><li>';
                        echo form_label(lang('rr_reqattrreason'), 'reason');
                        echo form_textarea(array('name' => 'reason', 'cols' => 30, 'rows' => 5));
                        echo '</li></ol>';
                        $tf = '';
                        $tf .='<div class="buttons">';
                        $tf .='<button type="submit" name="submit" id="submit" value="Add" class="savebutton saveicon">
                  '.lang('rr_add').'</button>';
                        $tf .= '</div>';
                        echo $tf;
                        echo form_close();
                    }
                }
                echo '</td></tr></table>';
                if (count($already_in_attr) > 0)
                {

                    echo '<table id="details"><caption>'.lang('rr_modreqattr').'</caption><theader><td>'.lang('rr_tbltitle_name').'</td><td>Oid</td><td>'.lang('rr_tbltitle_reason').'</td><td>'.lang('rr_tbltitle_status').'</td></theader><tbody>';
                    foreach ($already_in_attr as $a)
                    {
                        echo '<tr class="accordionButton">';
                        echo '<td>' . $a['fullname'] . '</td>';
                        echo '<td>' . $a['oid'] . '</td>';
                        echo '<td>' . $a['reason'] . '</td>';
                        echo '<td>' . $a['status'] . '</td>';
                        echo '</tr>';
                        echo '<tr class="accordionContent"><td colspan="4">';

                        $spid_hidden['attribute'] = $a['attr_id'];
                        $spid_hidden['type'] = 'SP';
                        echo form_open(base_url() . 'manage/attribute_requirement/submit', $attributes2, $spid_hidden);
                        echo form_fieldset($a['name']);
                        echo '<ol>';
                        echo '<div><b>"' . $a['fullname'] . '":</b> ' . $a['description'] . '<br />
			<b>SAML1:</b> ' . $a['urn'] . '<br />
			<b>SAML2:</b> ' . $a['oid'] . '</div>';
                        echo '<li>';
                        echo form_label(lang('rr_reqattr_currenttype'), 'requirement');
                        echo form_dropdown('requirement', array('desired' => ''.lang('dropdesired').'', 'required' => ''.lang('droprequired').''), $a['status']);
                        echo '</li><li>';
                        echo form_label(lang('rr_reqattrreason'), 'reason');
                        echo form_textarea(array('name' => 'reason', 'cols' => 30, 'rows' => 5, 'value' => $a['reason']));
                        echo '</li><ol>';
                        echo form_fieldset_close();
                        $tf = '';
                        $tf .='<div class="buttons">';
                        $tf .='<button type="submit" name="submit" value="Remove" class="resetbutton reseticon">
                  '.lang('rr_remove').'</button>';
                        $tf .='<button type="submit" name="submit" value="Modify" class="savebutton saveicon">
                  '.lang('rr_modify').'</button>';
                        $tf .= '</div>';
                        echo $tf;
                        echo form_close();
                        echo '</td></tr>';
                    }
                    echo '</tbody></table>';
                }
                ?>
