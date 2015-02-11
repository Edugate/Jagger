<?php

if(empty($bookmarked))
{
    echo '<span data-jagger-onsuccess="hide" class="hide-for-small-only"><a href="' . base_url() . 'ajax/bookfed/' . $federation_id . '" class="updatebookmark bookentity"  data-jagger-bookmark="add" title="Add to dashboard"><i class="fi-plus" style="color: white"></i></a></span>';
}

echo '<div id="ifedtabs">';
echo '<ul class="tabs" data-tab>
 <li class="tab-title active"><a href="#general">' . lang('tabgeneral') . '</a></li>
 <li class="tab-title"><a href="#membership">' . lang('tabMembership') . '</a></li>
 <li class="tab-title"><a href="#metadata">' . lang('rr_metadata') . '</a></li>
 <li class="tab-title"><a href="#attrs">' . lang('tabAttrs') . '</a></li>';
if(!empty($fvalidator))
{
  echo '<li class="tab-title"><a href="#fvalidators">' . lang('tabFvalidators') . '</a></li>';
}
echo '<li class="tab-title"><a href="#management">' . lang('tabMngt') . '</a></li>
 </ul>
 ';

$tmpl = array('table_open' => '<table id="detailsnosort" >');

echo '<div class="tabs-content">';
foreach ($result as $k => $v)
{
    if($k !== 'general')
    {
       echo '<div id="' . $k . '" class="content nopadding">';
    }
    else
    {
     
       echo '<div id="' . $k . '" class="content active nopadding">';
       echo '<div class="text-right">'.$editlink.'</div>';
    
    }
    $this->table->set_template($tmpl);
    echo $this->table->generate($v);
    $this->table->clear();
    echo '</div>';
}
echo '</div>';
echo '</div>';

echo confirmDialog(''.lang('title_confirm').'', ''.lang('douwanttoproceed').':', ''.lang('rr_yes').'', ''.lang('rr_no').'');

if(!empty($hiddenspan))
{
echo $hiddenspan;
}
?>
<div class="metadataresult" style="display: none"></div>
