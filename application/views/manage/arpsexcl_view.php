<?php
$errors_v = validation_errors('<div>', '</div>');
if (!empty($errors_v)) {
    echo '<div data-alert class="alert-box alert">'.$errors_v.'</div>';
}
if(!empty($success))
{
     echo '<div data-alert class="alert-box success">'.$success.'</div>';
}
if(!empty($rows) && is_array($rows))
{
    $hidden = array('idpid'=>$idp_id);
    $attrs = array('id'=>'arpexlusions');
    echo form_open(current_url(),$attrs,$hidden);
    echo form_fieldset();
    foreach($rows as $r)
    {
       echo '<div class="small-12 columns"><div class="small-11 medium-9 large-8 small-centered columns ">'.$r.'</div></div>';
      
    }
    echo form_fieldset_close();
    $btns = array(
        '<button type="reset" name="reset" value="reset" class="button alert">
                  '.lang('rr_reset').'</button>',
        '<button type="submit" name="modify" value="submit" class="button">
                  '.lang('rr_save').'</button>'
    );
    echo '<div class="small-12 columns">';

    echo revealBtnsRow($btns);
    echo  '</div>';
    echo form_close();
}
else
{
   echo '<div>'.lang('nospfoundtoexcl').'</div>';

}

