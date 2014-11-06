<?php



function confirmDialog($title, $msg, $yes, $no)
{
    $r = '<div id="sconfirm" class="small-12 columns hidden"><div class="title-header small-12 columns text-center">' . htmlentities($title) . '</div>
  <p class="message">' . htmlentities($msg) . '</p>
  <div class="small-12 columns button-groups">
       <div class="no simplemodal-close small-3 columns"><div class="button small alert">' . htmlentities($no) . '</div></div>
  <div class="yes small-3 columns "><div class="button small">' . htmlentities($yes) . '</div></div>
  </div>
  </div>';
    return $r;
}

function resultDialog($title, $msg, $close)
{
    $r = '<div id="resultdialog" class="hidden"><div class="header"><span>' . htmlentities($title) . '</span></div>
  <p class="message">' . htmlentities($msg) . '</p>
  <div class="buttons">
  <div class="no simplemodal-close">' . htmlentities($close) . '</div>
  </div>
  </div>';
    return $r;
}

function recurseTree($var)
{
    $out = '<li>';
    foreach ($var as $v)
    {
        if (is_array($v))
        {
            $out .= '<ul>' . recurseTree($v) . '</ul>';
        }
        else
        {
            $out .= $v;
        }
    }
    return $out . '</li>';
}

function generateSelectInputCheckboxFields($label1, $name1, $select1, $selected1, $label2,$name2, $value2,$label3, $name3,  $value3, $ifset3,$classes = null)
{
  $r  = '';
  if(!empty($label1))
  {
     $r  .= '<div class="small-3 columns">';
     $r .= '<label class="right inline" for="'.$name1.'">'.$label1.'</label></div>';
     
  }
  else
  {
     $r  .= '<div class="small-3 columns">&nbsp;</div>';
  }
  $r .= '<div class="small-5 columns inline">';
  $r .= form_dropdown($name1, $select1, $selected1);
  $r .= '</div>';

  $r .= '<div class="small-4 large-4 columns">'; // input+check
    $r .= '<div class="small-6 columns">';
    $r .= form_input(
          array(
            'name'=>$name2,
            'id'=>$name2,
            'size'=>'3',
            'max-length'=>'3',
            'class'=>'acsindex',
            'value'=>$value2,
          )
       );
     $r .= '</div>';
    $r .= '<div class="small-6 columns">';
    if(!empty($label3))
    {
       $r .= '<label for="'.$name3.'">'.$label3.'</label>';
    }
    $r .= form_radio(array(
         'name'=>$name3,
         'id'=>$name3,
         'value'=>$value3,
         'class'=>'acsdefault',
         'checked'=>$ifset3,
     ));
     $r .='</div>';


  $r .= '</div>'; // end input+check
  return $r;
}
function generateSelectInputFields($label1, $name1, $select1, $selected1, $label2,$name2, $value2,$classes = null)
{
  $r  = '';
  if(!empty($label1))
  {
     $r  .= '<div class="small-3 columns">';
     $r .= '<label class="right inline" for="'.$name1.'">'.$label1.'</label></div>';
     
  }
  else
  {
     $r  .= '<div class="small-3 columns">&nbsp;</div>';
  }
  $r .= '<div class="small-6 large-7 columns inline">';
  $r .= form_dropdown($name1, $select1, $selected1);
  $r .= '</div>';

  $r .= '<div class="small-2 large-1 columns end">'; // input+check
    $r .= form_input(
          array(
            'name'=>$name2,
            'id'=>$name2,
            'size'=>'3',
            'max-length'=>'3',
            'class'=>'acsindex',
            'value'=>$value2,
          )
       );

    $r .= '</div>';

  return $r;
}


function jGenerateInput($label,$inputname,$value,$inputclass,$placeholder=null)
{
   if(!empty($placeholder))
   {
       $pl = ' placeholder="'.$placeholder.'" ';
   }
   else
   {
       $pl = '';
   }
   $r = '<div class="medium-3 columns medium-text-right"><label for="'.$inputname.'" class="inline">'.$label.'</label></div>';
   $r .= '<div class="medium-8 large-7 columns end"><input type="text" id="'.$inputname.'" name="'.$inputname.'" value="'.$value.'" class="'.$inputclass.'" '.$pl.'></div>';
   return $r;
}
function jGenerateTextarea($label,$inputname,$value,$inputclass)
{
   $r = '<div class="medium-3 columns medium-text-right"><label for="'.$inputname.'" class="inline">'.$label.'</label></div>';
   $r .= '<div class="medium-8 large-7 columns end">'.form_textarea($inputname, $value).'</div>';
   return $r;
}
function jGenerateInputReadonly($label,$inputname,$value,$inputclass)
{
   $r = '<div class="medium-3 columns medium-text-right"><label for="'.$inputname.'" class="inline">'.$label.'</label></div>';
   $r .= '<div class="medium-8 large-7 columns end"><input type="text" id="'.$inputname.'" name="'.$inputname.'" value="'.$value.'" class="'.$inputclass.'" readonly="readonly"></div>';
   return $r;
}
function jGenerateDropdown($label, $inputname, $dropdowns, $value, $inputclass)
{
   $r = '<div class="medium-3 columns medium-text-right"><label for="'.$inputname.'" class="inline">'.$label.'</label></div>';

   $r .= '<div class="medium-8 large-7 columns end">'.form_dropdown($inputname, $dropdowns, $value).'</div>';


   return $r;
}

function jGenerateRadios($label,$inputname,$radios,$value,$inputclass)
{
     $r = '<div class="medium-3 columns medium-text-right"><label for="'.$inputname.'" class="inline">'.$label.'</label></div>';
     $r .= '<div class="medium-8 large-7 columns end">';
     foreach($radios as $k=>$p)
     {
         if($p['value'] === $value)
         {
             $checked = 'checked="checked"';
         }
         else
         {
             $checked = '';
         }
         $r .=  '<div class="small-12 column"><div class="small-1 column"><input type="radio" name="'.$inputname.'" value="'.$p['value'].'" id="'.$inputname.$k.'" '.$checked.'></div><div class="small-11 column"><label for="'.$inputname.$k.'">'.$p['label'].'</label></div></div>';
     }
     $r .='</div>';
     return $r;
}


function generateInputWithRemove($label, $name, $buttonname, $buttonvalue, $value , $inputclasses,$buttonclasses)
{       
    $result = '<div class="small-3 columns"><label for="' . $name . '" class="right inline ">' . $label . '</label></div><div class="small-6 large-7 columns">' . form_input(
                        array(
                            'name' => '' . $name . '',
                            'id' => '' . $name . '',
                            'value' => '' . $value . '',
                            'class' => $inputclasses . ' right inline'
                        )
                ) . '</div><div class="small-3 large-2 columns"><button type="button" class="btn inline left button tiny alert '.$buttonclasses.'" name="'.$buttonname.'" value="' . $buttonvalue . '">' . lang('rr_remove') . '</button></div>';

    return $result;
}

function jform_label($a,$b)
{
  return '<label form="'.$b.'" class="right inline">'.$a.'</label>';
}
