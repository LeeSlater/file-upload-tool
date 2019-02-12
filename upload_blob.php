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


// The absolute path to your 'uploads' directory
$uploads_root = "/var/www/site/public_html/custom/file-uploads/uploads";

// The default directory for uploads if no app_id is set
$uploads_dir = $uploads_root.'/'.'general';

// The return
$output = array();


if (!isset($_POST['blob_part'])) {
	error_log("(".$_SERVER['REMOTE_ADDR'].") "."Unable to upload blob: blob_part not set");
	$output['status'] = "3";
	echo json_encode($output);
	exit;
}


// There was an error uploading this file, please contact the website maintainers for assistance
if (!is_writable($uploads_root)) {
	error_log("(".$_SERVER['REMOTE_ADDR'].") "."Cannot write files to ".$uploads_root);
	$output['status'] = "1a";
	echo json_encode($output);
	exit;
}


// Set a more specific upload directory by testing for an app_id an unique_id in $_POST
if (isset($_POST['app_id'])) {
	if (!file_exists($uploads_root.'/'.$_POST['app_id'])) {
		mkdir($uploads_root.'/'.$_POST['app_id']);
	}
	chmod($uploads_root.'/'.$_POST['app_id'], 0777);
	$uploads_dir = $uploads_root.'/'.$_POST['app_id'];
	if (isset($_POST['unique_id'])) {
		if (!file_exists($uploads_dir.'/'.$_POST['unique_id'])) {
			mkdir($uploads_dir.'/'.$_POST['unique_id']);
		}
		chmod($uploads_dir.'/'.$_POST['unique_id'], 0777);
		$uploads_dir = $uploads_dir.'/'.$_POST['unique_id'];
	}
}


// If uploader_name has been set, create a sub-directory using this name
if (isset($_POST['uploader_name'])) {
	$uploads_dir = $uploads_dir.'/'.str_replace("/","",$_POST['uploader_name']);
	mkdir($uploads_dir);
	chmod($uploads_dir, 0777);
}


// Final check to make sure that we can write to the uploads directory
if (!file_exists($uploads_dir) || !is_writable($uploads_dir)) {
	error_log("(".$_SERVER['REMOTE_ADDR'].") "."Cannot write files to $uploads_dir");
	$output['status'] = '1b';
	echo json_encode($outputArray);
	exit();
}

error_log("Final uploads directory: ".$uploads_dir);
error_log("Blob being uploaded: ".$_POST['blob_name']);


// Move the blob into position with a unique name
$file_extension = pathinfo($uploads_dir."/".$_POST['blob_name'], PATHINFO_EXTENSION);
$output['ext'] = $file_extension;
$output['original_file'] = $_FILES[0];
$output['original_file_name'] = $_POST['blob_name'];
if (isset($_POST['unique_filename'])) {
	$output['unique_filename'] = $_POST['unique_filename'];
} else {
	$output['unique_filename'] = md5(uniqid(mt_rand())).".".$file_extension;
}
$target_file = $uploads_dir.'/'.$output['unique_filename'].".".$_POST['blob_part'];
error_log("(".$_SERVER['REMOTE_ADDR'].") ".$target_file);
if (move_uploaded_file($_FILES[0]["tmp_name"], $target_file)) {
	chmod($target_file, 0666);
	$output['status'] = '0';
	error_log("(".$_SERVER['REMOTE_ADDR'].") ".json_encode($outputArray));
	echo json_encode($output);
	exit;
} else {
	error_log("(".$_SERVER['REMOTE_ADDR'].") "."Failed to move "+$_FILES[0]["tmp_name"]+" to "+$target_file);
	$output['status'] = '2';
	echo json_encode($output);
	exit;
}
