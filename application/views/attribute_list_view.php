<?php
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @subpackage  Views
 * @author      Middleware Team HEAnet 
 *  
 */

if($isadmin)
{
?>
<div class="small-12 text-right"><a href="<?php echo base_url().'attributes/attributes/add'; ?>" class="addbutton addicon button small"><?php echo lang('rr_add');?></a></div>
<?php
}
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$tmpl = array('table_open' => '<table  id="details" class="tablesorter drop-shadow lifted">');

    $this->table->set_template($tmpl);
    $this->table->set_heading(''.lang('attrname').'',''.lang('attrfullname').'',''.lang('attroid').'',''.lang('attrurn').'');
    echo $this->table->generate($attributes);
?>
