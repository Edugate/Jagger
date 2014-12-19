
<?php
if(!empty($error_message))
{
    echo '<div data-alert class="alert-box alert">'.$error_message.'</div>';
}
if($showaddbutton)
{

echo '<div class="small-12 text-right"><a href="'.base_url().'manage/ec/add" class="button small">'.lang('addentcat_btn').'</a></div>';
 
}

if(!empty($rows))
{
     $tmpl = array('table_open' => '<table  id="details" class="tablesorter">');
     $this->table->set_template($tmpl);
     $this->table->set_heading(lang('rr_displayname'),lang('rr_attr_name'),lang('entcat_value') ,lang('entcat_description'),lang('rr_status'),lang('rr_action'));
     echo $this->table->generate($rows);
     $this->table->clear();    

}

echo '<div id="confirmremover" class="reveal-modal small" data-reveal>';
echo form_open();

echo '<h3>'.lang('douwanttoproceed').'</h3>';
echo '<p><div class="buttons small-12 columns small-text-right">
    <div class="small-6 columns">
    <div class="no close-reveal-modal button">' . lang('rr_no') . '</div>
        </div>
    <div class="small-6 columns">
    <div class="yes button">' . lang('rr_yes') . '</div>
        </div>
        
</div></p>';

echo form_close();
    
echo '</div>';