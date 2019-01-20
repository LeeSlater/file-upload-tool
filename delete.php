<?php

/**
 * Scan the uploads directory and delete the defined file if found
 */

$uploads_root = "/var/www/html/public_html/uploads";
$uploads_dir = $uploads_root;
if (isset($_POST['app_id'])) {
	echo $_POST['app_id'];
	if (file_exists($uploads_root.'/'.$_POST['app_id'])) {
		$uploads_dir = $uploads_root.'/'.$_POST['app_id'];
	}
}
if (isset($_POST['file_name'])) {
	scan_dir_for_file($uploads_dir, $_POST['file_name']);
}

function scan_dir_for_file($dir, $file) {
	echo "| Scanning ".$dir." ";
	$files = scandir($dir);
	foreach ($files as $f) {
		if ($f=="." || $f=="..") continue;
		echo " ".$f." ";
		if (is_dir($dir.'/'.$f)) {
			scan_dir_for_file($dir.'/'.$f, $file);
		} else {
			if ($f==$file) {
				unlink($dir.'/'.$f);
			}
		}
	}
}

