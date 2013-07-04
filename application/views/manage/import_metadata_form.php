<div id="pagetitle"><?php echo lang('titleimportmeta');?></div>

<div>
    <?php
    $base = base_url();
    $attributes = array('class' => 'email', 'id' => 'formver2');
    $current_class = $this->router->class;
    echo form_open("" . $base . "manage/" . $current_class . "/submit", $attributes);
    $errors_v = validation_errors('<span class="span-12">', '</span><br />');
    if(!empty($errors_v))
    {
    	echo '<div class="error">';
    	echo $errors_v;
    	echo "</div>";
    }
    if(!empty($other_error))
    {
    	echo '<div class="error">';
    	echo $other_error;
    	echo "</div>";
    }

    echo form_fieldset(lang('metalocation')) ;
    echo '<ol><li>';
    echo form_label(lang('metatypeent'), 'type');
    echo form_dropdown('type', $types) ;
    echo '</li> <li>';
    echo form_label(lang('rr_federation'), 'federation');
    echo form_dropdown('federation', $federations);
    echo '</li><li>';
    echo form_label(lang('metalocation'), 'metadataurl');
    echo form_input(array('name' => 'metadataurl', 'id' => 'metadataurl', 'placeholder' => 'http://example.com/example-metadata.xml', 'value' => set_value('metadataurl'), 'required' => 'required'));
    echo '</li><li>';
    echo form_label(lang('importsslcheck'), 'sslcheck');
    echo form_checkbox('sslcheck', 'ignore', FALSE);
    echo '</li></ol>';
    echo form_fieldset_close();

    echo form_fieldset(lang('rr_options'));
    echo '<ol><li>';
    echo form_label(lang('importasintext'), 'extorint');
    $choices = array('' => ''.lang('rr_pleaseselect').'', 'int' => ''.lang('internal').'', 'ext' => ''.lang('external').'');
    echo form_dropdown('extorint', $choices);
    echo '</li><li>';
    echo form_label(''.lang('tooverwritelocal').'', 'overwrite');
    $choices = array('' => ''.lang('rr_pleaseselect').'', 'yes' => ''.lang('rr_yes').'', 'no' => ''.lang('rr_no').'');
    echo form_dropdown('overwrite', $choices);
    echo '</li><li>';
    echo form_label(''.lang('newentenabled').'', 'active');
    $choices = array('' => ''.lang('rr_pleaseselect').'', 'yes' => ''.lang('rr_yes').'', 'no' => ''.lang('rr_no').'');
    echo form_dropdown('active', $choices);
    echo '</li><li>';
    echo form_label(''.lang('populateallinf').'', 'fullinformation');
    $choices = array('' => ''.lang('rr_pleaseselect').'', 'yes' => ''.lang('rr_yes').'', 'no' => ''.lang('rr_no').'');
    echo form_dropdown('fullinformation', $choices);
    echo '</li><li>';
    echo form_label(''.lang('staticenabledbydefault').'', 'static');
    $choices = array('' => ''.lang('rr_pleaseselect').'', 'yes' => ''.lang('rr_yes').'', 'no' => ''.lang('rr_no').'');
    echo form_dropdown('static', $choices);
    echo '</li></ol>';
    echo form_fieldset_close();
   
    echo form_fieldset(''.lang('metavalidation').'');
    echo '<span class="alert"> '.lang('notworkingyet').'</span>';
    echo '<ol><li>';
    echo form_label(''.lang('metavalidatewithcert').'', 'validate');
    echo form_checkbox('validate', 'accept', FALSE);
    echo '</li><li>';
    echo form_label(''.lang('urlofcertsigner').'', 'certurl');
    echo form_input(array('name' => 'certurl', 'id' => 'certurl', 'value' => set_value('certurl'), 'placeholder' => 'http://example.com/metadata-signer.crt'));
    echo '</li><li>';
    echo form_label(''.lang('certsigner').' <br /><small><i>'.lang('overwritecerturl').'</i></small>', 'cert');
    echo form_textarea(array('name' => 'cert', 'id' => 'cert', 'cols' => 30, 'rows' => 15, 'value' => set_value('cert')));
    echo '</li></ol>';
     ?>
        <div class="buttons">
              <button type="submit" name="submit" value="Import" class="btn positive">
                 <span class="save">Import<span></button>
        </div>
    <?php
    echo form_fieldset_close();
    echo form_close();
    ?>
</div>
