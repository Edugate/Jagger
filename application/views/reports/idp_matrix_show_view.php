<?php


if(!empty($entityid))
{
   echo '<div id="subtitle">';
   $imgsrc = '<img src="'.base_url().'images/icons/home.png" />';
   echo 'Attribute release policy overview by entityID: '. $entityid . ' '.anchor(''.base_url().'providers/provider_detail/idp/'.$idpid,$imgsrc) ;
   echo '</div>';
}
if(!empty($error_message))
{
  echo '<div class="span-16 alert">'.$error_message.'</div>';
}

if(!empty($result))
{
   $tmpl = array('table_open' => '<table  id="idpmatrix" class="zebra" >');
   $this->table->set_template($tmpl);
   $this->table->set_empty('&nbsp;');
echo   $this->table->generate($result);
}
