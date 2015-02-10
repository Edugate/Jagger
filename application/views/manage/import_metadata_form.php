
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
    if(!empty($other_error) && count($other_error)>0)
    {
        foreach($other_error as $v)
        {
    	  echo '<div class="error">';
    	  echo htmlentities($v);
    	  echo "</div>";
        }
    }
    if(!empty($global_erros) && count($global_erros)>0)
    {
       foreach($global_erros as $v)
       {
    	echo '<div class="error">';
    	echo $v;
    	echo "</div>";
           
       }
    }
    echo form_fieldset(lang('metalocation')) ;
    echo '<div class="small-12 columns">';
    echo '<div class="small-3 columns"><label for="type" class="right inline">'.lang('metatypeent').'</label></div>';
    echo '<div class="small-6 large-7 columns end">'.form_dropdown('type', $types,set_value('type')).'</div>' ;
    echo '</div>';
    echo '<div class="small-12 columns">';
    echo '<div class="small-3 columns"><label for="federation" class="right inline">'.lang('rr_federation').'</label></div>';
    echo '<div class="small-6 large-7 columns end">'.form_dropdown('federation', $federations,set_value('federation')).'</div>';
    echo '</div>';
    echo '<div class="small-12 columns">';
    echo '<div class="small-3 columns"><label for="metadataurl" class="right inline">'.lang('metalocation').'</label></div>';
    echo '<div class="small-6 large-7 columns end">'.form_input(array('name' => 'metadataurl', 'id' => 'metadataurl', 'placeholder' => 'http://example.com/example-metadata.xml', 'value' => set_value('metadataurl','',FALSE), 'required' => 'required')).'</div>';
    echo '</div>';
    echo '<div class="small-12 columns">';
    echo '<div class="small-3 columns"><label for="sslcheck" class="right inline">'.lang('importsslcheck').'</label></div>';
    ?>
      <div class="small-6 large-7 columns end"><input type="checkbox" id="sslcheck" name="sslcheck" value="ignore" <?php echo set_checkbox('sslcheck', 'ignore'); ?> /></div>
    <?php
    echo '</div>';
    echo form_fieldset_close();

    echo form_fieldset(lang('rr_options'));
    echo '<ol><li>';
    echo '<div class="small-12 columns">';
    echo '<div class="small-3 columns">';
    echo '<label for="extorint" class="right inline">'.lang('importasintext').'</label>';
    echo '</div>';
    echo '<div class="small-6 large-7 columns end">';
    $choices = array('' => ''.lang('rr_pleaseselect').'', 'int' => ''.lang('internal').'', 'ext' => ''.lang('external').'');
    echo form_dropdown('extorint', $choices,set_value('extorint'));
    echo '</div>';
    echo '</div>';
    echo '<div class="small-12 columns">';
    echo '<div class="small-3 columns">';
    echo '<label for="overwrite" class="right inline">'.lang('tooverwritelocal').'</label>';
    echo '</div>';
    echo '<div class="small-6 large-7 columns end">';
    $choices = array('' => ''.lang('rr_pleaseselect').'', 'yes' => ''.lang('rr_yes').'', 'no' => ''.lang('rr_no').'');
    echo form_dropdown('overwrite', $choices,set_value('overwrite'));
    echo '</div>';
    echo '</div>';
    echo '<div class="small-12 columns">';
    echo '<div class="small-3 columns">';
    echo '<label for="active" class="right inline">'.lang('newentenabled').'</label>';
    echo '</div>';
    echo '<div class="small-6 large-7 columns end">';
    $choices = array('' => ''.lang('rr_pleaseselect').'', 'yes' => ''.lang('rr_yes').'', 'no' => ''.lang('rr_no').'');
    echo form_dropdown('active', $choices,set_value('active'));
    echo '</div>';
    echo '</div>';
    echo '<div class="small-12 columns">';
    echo '<div class="small-3 columns">';
    echo '<label for="fullinformation" class="right inline">'.lang('populateallinf').'</label>';
    echo '</div>';
    echo '<div class="small-6 large-7 columns end">';
    $choices = array('' => ''.lang('rr_pleaseselect').'', 'yes' => ''.lang('rr_yes').'', 'no' => ''.lang('rr_no').'');
    echo form_dropdown('fullinformation', $choices,set_value('fullinformation'));
    echo '</div>';
    echo '</div>';
    echo '<div class="small-12 columns">';
    echo '<div class="small-3 columns">';
    echo '<label for="static" class="right inline">'.lang('staticenabledbydefault').'</label>';
    echo '</div>';
    echo '<div class="small-6 large-7 columns end">';
    $choices = array('' => ''.lang('rr_pleaseselect').'', 'yes' => ''.lang('rr_yes').'', 'no' => ''.lang('rr_no').'');
    echo form_dropdown('static', $choices,set_value('static'));
    echo '</div>';
    echo '</div>';
    echo form_fieldset_close();


   
    echo form_fieldset(''.lang('metavalidation').'');
    echo '<div class="small-12 columns">';
    echo '<div class="small-3 columns">';
    echo '<label for="validate" class="right inline">'.lang('metavalidatewithcert').'</label>';
    echo '</div>';
    echo '<div class="small-6 large-7 columns end">';
    ?>
    <input type="checkbox" id="validate" name="validate" value="accept" <?php echo set_checkbox('validate', 'accept',TRUE); ?> />
    <?php
    echo '</div>';
    echo '</div>';
    echo '<div class="small-12 columns">';
    echo '<div class="small-3 columns">';
    echo '<label for="certurl" class="right inline">'.lang('urlofcertsigner').'</label>';
    echo '</div>';
    echo '<div class="small-6 large-7 columns end">';
    echo form_input(array('name' => 'certurl', 'id' => 'certurl', 'value' => set_value('certurl','',FALSE), 'placeholder' => 'http://example.com/metadata-signer.crt'));
    echo '</div>';
    echo '</div>';
    echo '<div class="small-12 columns">';
    echo '<div class="small-3 columns">';
    echo '<label for="cert" class="right inline">'.lang('certsigner').'<br /><small><i>'.lang('overwritecerturl').'</i></small></label>';
    echo '</div>';
    echo '<div class="small-6 large-7 columns end">';
    echo form_textarea(array('name' => 'cert', 'id' => 'cert', 'cols' => 30, 'rows' => 15, 'value' => set_value('cert','',FALSE)));
    echo '</div>';
    echo '</div>';
    echo form_fieldset_close();
    echo '<div class="buttons small-9 columns end right-text"><div class="small-1 right columns end"><button type="submit" name="submit" value="Import" class="button savebutton saveicon">'.lang('btn_import').'</button></div></div>';
    echo form_close();
    ?>
</div>
