<?php

$experimentalTheme = $this->config->item('experimentaltheme');

if(empty($experimentalTheme))
{
   $this->load->view('frontend');
}
else
{
   $this->load->view('frontend-experimental');
}
