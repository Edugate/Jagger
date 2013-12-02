<?php

function confirmDialog($title, $msg, $yes, $no)
{
    $r = '<div id="sconfirm"><div class="header"><span>' . htmlentities($title) . '</span></div>
  <p class="message">' . htmlentities($msg) . '</p>
  <div class="buttons">
  <div class="no simplemodal-close">' . htmlentities($no) . '</div>
  <div class="yes">' . htmlentities($yes) . '</div>
  </div>
  </div>';
    return $r;
}
function resultDialog($title,$msg,$close)
{
     $r = '<div id="resultdialog"><div class="header"><span>' . htmlentities($title) . '</span></div>
  <p class="message">' . htmlentities($msg) . '</p>
  <div class="buttons">
  <div class="no simplemodal-close">' . htmlentities($close) . '</div>
  </div>
  </div>';
    return $r;
}

function recurseTree($var){
  $out = '<li>';
  foreach($var as $v){
    if(is_array($v)){
      $out .= '<ul>'.recurseTree($v).'</ul>';
    }else{
      $out .= $v;
    }
  }
  return $out.'</li>';
}
