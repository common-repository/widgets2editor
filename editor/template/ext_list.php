<?php
header("content-type: application/x-javascript");

if ($_GET['ids'] && $_GET['names']) {
	$ids = explode(',',$_GET['ids']);
	$names = explode(',',$_GET['names']);
	if (count($ids) == count($names)) {
?>
	var tinyMCETemplateList = [
	<?php
	 foreach ((array) $ids as $key=>$value) {
	 	if (count($ids) == $key + 1) {
	 		echo '["'.$names[$key].'", "/?tiny='.$value.'", "'.$names[$key].'."]';
	 	} else {
	 		echo '["'.$names[$key].'", "/?tiny='.$value.'", "'.$names[$key].'."],'."\n";
	 	}
	 }
	?>
	];
<?php
	} else {
		?>
		var tinyMCETemplateList = [];
		<?php
	}
} else {
?>
	var tinyMCETemplateList = [];
<?php
}
?>