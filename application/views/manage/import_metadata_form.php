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

    echo form_fieldset('Metadata location') . "\n";
    echo "<ol>\n";
    echo "<li>\n";
    echo form_label('The type of entities', 'type');
    echo form_dropdown('type', $types) . "\n";
    echo "</li>\n";
    
    echo "<li>";
    echo form_label('Federation', 'federation');
    echo form_dropdown('federation', $federations);
    echo "</li>\n";
    echo "<li>";
    echo form_label('The URL of Metadata location', 'metadataurl');
    echo form_input(array('name' => 'metadataurl', 'id' => 'metadataurl', 'placeholder' => 'http://edugate.heanet.ie/edugate-metadata.xml', 'value' => set_value('metadataurl'), 'required' => 'required'));
    echo "</li>\n";
    echo "</ol>\n";
    echo form_fieldset_close();

    echo form_fieldset('Options');
    echo "<ol>\n";
    echo "<li>\n";
    echo form_label('Import entities as external or internal', 'extorint');
    $choices = array('' => 'Please select', 'int' => 'Internal', 'ext' => 'External');
    echo form_dropdown('extorint', $choices);
    echo "</li>\n";
    echo "<li>\n";
    echo form_label('Do you want to overwrite entity even if it\'s local', 'overwrite');
    $choices = array('' => 'Please select', 'yes' => 'Yes', 'no' => 'No');
    echo form_dropdown('overwrite', $choices);
    echo "</li>\n";
    echo "<li>\n";
    echo form_label('New entities enabled by default', 'active');
    $choices = array('' => 'Please select', 'yes' => 'Yes', 'no' => 'No');
    echo form_dropdown('active', $choices);
    echo "</li>\n";
   
    echo "<li>\n";
    echo form_label('Populate all information', 'fullinformation');
    $choices = array('' => 'Please select', 'yes' => 'Yes', 'no' => 'No');
    echo form_dropdown('fullinformation', $choices);
    echo "</li>\n"; 
    echo "<li>\n";
    echo form_label('Should static metadata be enabled by default   ', 'static');
    $choices = array('' => 'Please select', 'yes' => 'Yes', 'no' => 'No');
    echo form_dropdown('static', $choices);
    echo "</li>\n";
    echo "</ol>\n";
    echo form_fieldset_close();
   
    echo form_fieldset('Metadata validation (optional) ');
    echo "<span class=\"alert\"> not working yet!</span>";
    echo "<ol>\n";
    echo "<li>\n";
    echo form_label('Validate metadata with certificate', 'validate');
    echo form_checkbox('validate', 'accept', FALSe);
    echo "</li>\n";
    echo "<li>";
    echo form_label('URL of location of metadata signing certificate', 'certurl');
    echo form_input(array('name' => 'certurl', 'id' => 'certurl', 'value' => set_value('certurl'), 'placeholder' => 'http://edugate.heanet.ie/metadata-signer.crt'));
    echo "</li>\n";
    echo "<li>\n";
    echo form_label('Metadata signing certificate in X509 format <small>overwrites URL of cert</small>', 'cert');
    echo form_textarea(array('name' => 'cert', 'id' => 'cert', 'cols' => 30, 'rows' => 15, 'value' => set_value('cert')));
    echo "</li>\n";
    echo "</ol>\n";
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
