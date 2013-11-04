<div id="pagetitle"><?php echo lang('rr_arpexcl1');?></div>
<div id="subtitle"><h3><?php echo anchor(base_url().'providers/detail/show/'.$idp_id, $idp_name ) ;?></h3><h4><?php echo $idp_entityid;?></h4></div>

<?php

if(!empty($rows) && is_array($rows))
{
    $attrs = array('id'=>'arpexlusions');
    echo form_open(current_url(),$attrs);
    echo form_fieldset();
    echo '<ol>';
    foreach($rows as $r)
    {
       echo '<li>'.$r.'</li>';
      
    }
    echo '</ol>';
    echo form_fieldset_close();
    echo '<div class="buttons">';
    echo '<button type="reset" name="reset" value="reset" class="resetbutton reseticon">
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

