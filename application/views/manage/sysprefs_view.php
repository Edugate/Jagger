<?php


$this->table->set_heading('Name', 'Description', 'Values', 'Status', 'Action');
$colspan = 5;
foreach ($datas as $x => $y) {
	$cell = array('data' => 'Category: ' . $x, 'class' => 'highlight', 'colspan' => $colspan);
	$this->table->add_row($cell);
	foreach ($y as $k => $v) {
		if ($v['type'] === 'text') {
			$cell = array();
			$cell[] = '<span data-jagger-name="displayname">' . $v['displayname'] . '</span>';
			$cell[] = '<span data-jagger-name="desc">' . $v['desc'] . '</span>';
			$cell[] = '<span data-jagger-name="vtext">' . nl2br($v['vtext']) . '</span>';
			if ($v['status']) {
				$cell[] = '<span class="label" data-jagger-name="status">' . lang('rr_enabled') . '</span>';
			} else {
				$cell[] = '<span class="label alert" data-jagger-name="status">' . lang('rr_disabled') . '</span>';
			}
			$cell[] = '<a href="' . base_url() . 'smanage/sysprefs/retgconf/' . $v['confname'] . '" class="updateprefs" data-jagger-record="' . $v['confname'] . '"><i class="fi-pencil"></i></a>';

		}
		elseif($v['type'] === 'bool')
		{
			$cell = array();
			$cell[] = '<span data-jagger-name="displayname">' . $v['displayname'] . '</span>';
			$cell[] = '<span data-jagger-name="desc">' . $v['desc'] . '</span>';
			$cell[] = '<span data-jagger-name="vtext" class="label">'.lang('fieldnotusedbystt').'</span>';
			if ($v['status']) {
				$cell[] = '<span class="label" data-jagger-name="status">' . lang('rr_enabled') . '</span>';
			} else {
				$cell[] = '<span class="label alert" data-jagger-name="status">' . lang('rr_disabled') . '</span>';
			}
			$cell[] = '<a href="' . base_url() . 'smanage/sysprefs/retgconf/' . $v['confname'] . '" class="updateprefs" data-jagger-record="' . $v['confname'] . '"><i class="fi-pencil"></i></a>';

		}


		$this->table->add_row($cell);
	}
}


echo $this->table->generate();


?>

<div id="updateprefsmodal" class="reveal-modal medium" data-reveal
     data-jagger-link="<?php echo base_url() ?>smanage/sysprefs/updateconf">
	<h3>Update prefs for <span data-jagger-name="displayname"></span></h3>

	<div data-alert class="alert-box alert"></div>
	<div class="callout panel" data-jagger-name="desc"></div>

	<?php
	echo form_open();
	echo form_hidden('confname');
	echo '<div class="row">';
	echo '<div class="medium-3 column medium-text-right"><label for="status" class="">' . lang('mtmplenabled') . '</label></div>';
	echo '<div class="medium-9 column"><input type="checkbox" name="status" value="1"></div>';
	echo '</div>';

	echo '<div class="row">';
	echo '<div class="medium-3 column medium-text-right"><label for="vtext" class="">' . lang('label_text') . '</label></div>';
	echo '<div class="medium-9 column"><textarea name="vtext" data-jagger-name="vtext" rows="5"></textarea></div>';
	echo '</div>';
	echo '<div class="rows">';
    $btns = array(
        '<button type="reset" name="cancel" value="cancel" class="button alert modal-close">' . lang('rr_cancel') . '</button>',
        '<button type="submit" name="update" value="updateprefs" class="button">' . lang('btnupdate') . '</button>'
    );
    echo revealBtnsRow($btns);
	echo '</div>';
	echo form_close();

	?>




	<a class="close-reveal-modal">&#215;</a>
</div>

