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
<div id="subtitle"><h3><?php echo 'system reporter'; ?></h3></div>
<table class="details"><tbody>
<tr><td><?php echo lang('rr_ormvalidate');?></td><td><button id="vormversion" class="savebutton"><?php echo lang('rr_runprocess'); ?></button></td></tr>
<tr id="rvormversion" style="display:none"><td colspan="2"></td></tr>
<tr><td><?php echo lang('rr_validatedbschema');?></td><td><button id="vschema" class="savebutton"><?php echo lang('rr_runprocess');?></button></td></tr>
<tr id="rvschema" style="display:none"><td colspan="2"></td></tr>
<tr><td><?php echo lang('rr_validatedbsync');?></td><td><button id="vschemadb" class="savebutton"><?php echo lang('rr_runprocess');?></button></td></tr>
<tr id="rvschemadb" style="display:none"><td colspan="2"></td></tr>
<tr><td><?php echo lang('rr_runmigration');?></td><td><button id="vmigrate" class="savebutton"><?php echo lang('rr_runprocess');?></button></td></tr>
<tr id="rvmigrate" style="display:none" ><td colspan="2"></td></tr>
<!--
<tr><td><?php echo lang('rr_cleanoldlogs1');?></td><td><button id="vcleanarplogs" class="savebutton"><?php echo lang('rr_runprocess');?></button></td></tr>
<tr id="rvcleanarplogs" style="display:none"><td colspan="2"></td></tr>
-->
<!--
<tr><td></td><td><button class="savebutton">check</button></td></tr>
<tr><td colspan="2"></td></tr>
<tr><td></td><td><button class="savebutton">check</button></td></tr>
<tr><td colspan="2"></td></tr>
-->
</tbody></table>
