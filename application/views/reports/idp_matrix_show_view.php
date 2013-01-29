<div id="pagetitle"><?php echo lang('rr_arpoverview');?></div>
<?php


if(!empty($entityid))
{
   echo '<div id="subtitle"><h3>';
   echo lang('identityprovider').': '.anchor(''.base_url().'providers/provider_detail/idp/'.$idpid,$idpname) ;
   echo '</h3><h4>'.$entityid.'</h4></div>';
}
if(!empty($error_message))
{
  echo '<div class="span-16 alert">'.$error_message.'</div>';
}

if(!empty($result))
{
   $tmpl = array('table_open' => '<table  id="idpmatrix" class="zebra">');
   $this->table->set_template($tmpl);
   $this->table->set_empty('&nbsp;');
   echo   $this->table->generate($result);
   $this->table->clear();
}
