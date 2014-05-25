<?php
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @subpackage  Views
 * @author      Middleware Team HEAnet 
 *  
 */



?>
<div id="subtitle"><h3><?php echo lang('attrsdeflist');?></h3></div>
<?php
if($isadmin)
{
?>
<div style="float: right; witdth: 99%;"><a href="<?php echo base_url().'attributes/attributes/add'; ?>" class="addbutton addicon"><?php echo lang('rr_add');?></a></div>
<div style="clear: both;"></div>
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
