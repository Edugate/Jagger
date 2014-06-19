<?php

if(!empty($rows) && is_array($rows))
{
    $attrs = array('id'=>'arpexlusions');
    echo form_open(current_url(),$attrs);
    echo form_fieldset();
    foreach($rows as $r)
    {
       echo '<div class="small-12 columns"><div class="small-11 medium-9 large-8 small-centered columns ">'.$r.'</div></div>';
      
    }
    echo form_fieldset_close();
    echo '<div class="buttons mall-11 medium-9 large-8 small-centered columns">';
    echo '<button type="reset" name="reset" value="reset" class="resetbutton reseticon alert">
                  '.lang('rr_reset').'</button> ';
    echo '<button type="submit" name="modify" value="submit" class="savebutton saveicon">
                  '.lang('rr_save').'</button>';
    echo  '</div>';
    echo form_close();
}
else
{
   echo '<div>'.lang('nospfoundtoexcl').'</div>';

}

