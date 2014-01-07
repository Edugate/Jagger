<?php
if (!$bookmarked)
{
    $blink = '<a href="' . base_url() . 'ajax/bookfed/' . $federation_id . '" class="bookentity"><img src="' . base_url() . 'images/icons/star--plus.png" /></a>';
}
else
{
    $blink = '<a href="' . base_url() . 'ajax/delbookfed/' . $federation_id . '" class="bookentity"><img src="' . base_url() . 'images/icons/star--minus.png" /></a>';
}
?>
<div id="subtitle"><h3><?php echo lang('rr_feddetail') . ': ' . $federation_name . '  ' . $blink; ?></h3></div>

<?php
echo '<div id="fedtabs">';
echo '<ul>
 <li><a href="#general">' . lang('tabgeneral') . '</a></li>
 <li><a href="#membership">' . lang('tabMembership') . '</a></li>
 <li><a href="#metadata">' . lang('rr_metadata') . '</a></li>
 <li><a href="#attrs">' . lang('tabAttrs') . '</a></li>';
if(!empty($fvalidator))
{
  echo '<li><a href="#fvalidators">' . lang('tabFvalidators') . '</a></li>';
}
echo '<li><a href="#management">' . lang('tabMngt') . '</a></li>
 </ul>
 ';

$tmpl = array('table_open' => '<table id="detailsnosort" class="zebra">');


foreach ($result as $k => $v)
{
    echo '<div id="' . $k . '" class="nopadding">';
    $this->table->set_template($tmpl);
    $this->table->set_heading('', '' . lang('coldetails') . '');
    echo $this->table->generate($v);
    $this->table->clear();
    echo '</div>';
}

echo '</div>';
