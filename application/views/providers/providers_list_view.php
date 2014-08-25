<?php
$prefurl = base_url().'providers/providers_list/show/'.$entitytype.'';

$form = '<form id="filter-form"><input name="filter" id="filter" value="" placeholder="'.lang('rr_search').'" size="30" type="text"></form>';


echo '<div class="row">';
echo '<div class="small-10 medium-9 large-9 columns">';

echo '<dl class="sub-nav"> <dt>'.lang('rr_filter').':</dt> <dd class="afilter filterall"><a href="'.$prefurl.'/all" class="afilter filterall initiated">'.lang('allprov').'</a></dd> <dd class="afilter filterext"><a href="'.$prefurl.'" class="afilter filterext">'. lang('extprov').'</a></dd> <dd class="afilter filterlocal active"><a href="'.$prefurl.'" class="afilter filterlocal">'.lang('localprov').'</a></dd> </dl>';

echo '</div>';


echo '<div class="small-2 medium-3 large-3 columns">';
echo $form;
echo '</div>';

echo '</div>';
echo '<div data-alert class="alert-box alert hidden"></div>';

echo '<div id="providerslistresult"></div>';

echo '<div class="subtitleprefix hidden">'.lang('rr_found').'</div>';
