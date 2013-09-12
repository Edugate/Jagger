<?php

function corepreferences()
{
  $y = array(
     'cookieConsent'=>array('longname'=>'cookie consent','enabled'=>'0','val'=>'Company uses cookies to your browsing experience and to create a secure and effective website for our customers. By using this site you agree that we may temporary store and access cookies on your devices, unless you have disabled your cookies','desc'=>'display cookie consent on top of page'),
     'pageFooter'=>array('longname'=>'page footer text','enabled'=>'0','val'=>'Resource Registry','desc'=>'displays text in the footer on every page'),
     'rr_display_memory_usage'=>array('longname'=>'display memory usage','enabled'=>'0','val'=>'','desc'=>'display memory usage in the footer'),
    );

  return $y;
}

