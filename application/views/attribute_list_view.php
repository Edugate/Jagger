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
<div class="small-12 text-right"><a href="<?php echo base_url('attributes/attributes/add'); ?>" class="button"><?php echo lang('addattr_btn');?></a></div>
<?php
}

$tmpl = array('table_open' => '<table  id="details" class="tablesorter">');

    $this->table->set_template($tmpl);
    $this->table->set_heading(''.lang('attrname').'',''.lang('attrfullname').'',''.lang('attrsaml2').'',''.lang('attrsaml1').'');
    echo $this->table->generate($attributes);
?>
