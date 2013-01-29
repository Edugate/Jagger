<?php
if (!empty($javascript)) {
    echo $javascript;
}
?>
<div id="subtitle"><h3>
    Service Provider's name: <?php echo   anchor(base_url() . "providers/provider_detail/sp/" . $sp_detail['id'], $sp_detail['name']); ?>
</h3><h4>
<?php echo $sp_detail['entityid']; ?>
</h4></div>

<?php
if (!empty($error_message)) {
    echo "<span class=\"alert\">$error_message</span>";
}
if (!empty($error_messages)) {
    echo $error_messages;
}
/*
  if (!empty($form))
  {

  echo $form;
  }
 */
$action = base_url() . "manage/sp_edit/submit";
$attributes = array('id' => 'formver2');
echo form_open($action, $attributes);
echo $entityform;
//echo form_fieldset();
//echo "<ol><li>";
//echo form_reset('reset', 'reset') . form_submit('modify', 'submit');

//echo "</li></ol>";
$tf = '';

$tf .='<div class="buttons">';
$tf .='<button type="reset" name="reset" value="reset" class="button negative">
                  <span class="reset">Reset</span></button>';
$tf .='<button type="submit" name="modify" value="submit" class="button positive">
                  <span class="save">Save</span></button>';
$tf .= '</div>';
//echo form_fieldset_close();

echo $tf;

echo form_close();

