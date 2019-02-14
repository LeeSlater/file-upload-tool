<?php

/*
file-upload-tool - JavaScript and PHP web-based file uploader
Copyright (C) 2018  Lee Slater

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/


/**
 * Scan the uploads directory and delete the defined file if found
 */

$uploads_root = "/var/www/jadu/public_html/site/custom_scripts/repo/apps/file-uploads/uploads";
$uploads_dir = $uploads_root;
if (isset($_POST['app_id'])) {
	if (file_exists($uploads_root.'/'.$_POST['app_id'])) {
		$uploads_dir = $uploads_root.'/'.$_POST['app_id'];
	}
}
if (isset($_POST['file_name'])) {
	scan_dir_for_file($uploads_dir, $_POST['file_name']);
}

function scan_dir_for_file($dir, $file) {
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

