
<?php
if(!empty($error_message))
{
    echo '<div data-alert class="alert-box alert">'.$error_message.'</div>';
}
if($showaddbutton)
{

echo '<div class="small-12 text-right"><a href="'.base_url().'manage/regpolicy/add" class="button small">'.lang('addregpol_btn').'</a></div>';
 
}

if(!empty($rows))
{
     $tmpl = array('table_open' => '<table  id="details" class="tablesorter">');
     $this->table->set_template($tmpl);
     $this->table->set_heading(lang('regpol_shortname'),lang('regpol_language'),lang('rr_status'),lang('regpol_url'),lang('regpol_description'),lang('rr_action'));
     echo $this->table->generate($rows);
     $this->table->clear();    

}
