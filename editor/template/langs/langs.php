<?php
$lang_file = dirname(__FILE__) . '/' . $mce_locale . '_dlg.js';

if ( is_file($lang_file) && is_readable($lang_file) ) {
	$path = realpath($lang_file);
	$strings = @file_get_contents($path);
}
else {
	$path = realpath(dirname(__FILE__) . '/en_dlg.js');
	$strings = @file_get_contents($path);
	$strings = preg_replace( '/([\'"])en\./', '$1'.$mce_locale.'.', $strings, 1 );
}
