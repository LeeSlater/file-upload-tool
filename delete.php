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

require_once("class.file_manager.php");
$file_manager = new file_manager();

if (isset($_POST['app_id'])) {
	if (file_exists($file_manager->uploads_root.'/'.$_POST['app_id'])) {
		$file_manager->uploads_dir = $file_manager->uploads_root.'/'.$_POST['app_id'];
	}
}

$file_manager->delete_file($_POST['file_name']);

