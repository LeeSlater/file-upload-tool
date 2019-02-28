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



include_once '/var/www/jadu/public_html/site/custom_scripts/repo/logs/RBGerror_log.php';
set_error_log(__FILE__);
session_start();

// The absolute path to your 'uploads' directory
$uploads_root = "/var/www/jadu/public_html/site/custom_scripts/repo/apps/file-uploads/uploads";

// The default directory for uploads if no app_id is set
$uploads_dir = $uploads_root.'/'.'general';

// Per-file size limit
$allowed_bytes = 100000000;

// File permissions for directories and files generated by this script
$directory_permissions = 0777;
$file_permissions = 0666;



// Error handling
if (!isset($_POST['blob_part'])) {
	error_log("(".$_SERVER['REMOTE_ADDR'].") "."Unable to upload blob: blob_part not set");
	$output['status'] = "3";
	echo json_encode($output);
	exit;
}
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
	chmod($uploads_root.'/'.$_POST['app_id'], $directory_permissions);
	$uploads_dir = $uploads_root.'/'.$_POST['app_id'];
	if (isset($_POST['unique_id'])) {
		if (!file_exists($uploads_dir.'/'.$_POST['unique_id'])) {
			mkdir($uploads_dir.'/'.$_POST['unique_id']);
		}
		chmod($uploads_dir.'/'.$_POST['unique_id'], $directory_permissions);
		$uploads_dir = $uploads_dir.'/'.$_POST['unique_id'];
	} else {
		// Unique ID not manually set, check for userFormID to use as unique ID
		include_once("JaduConstants.php");
		include_once("xforms2/JaduXFormsUserForms.php");
		$userID = isset($_SESSION['userID']) ? $_SESSION['userID'] : -1;
		$unregisteredUserID = isset($_SESSION['unregisteredUserID']) ? $_SESSION['unregisteredUserID'] : -1;
		$userForm = getIncompleteFormIfExistsForUser($userID, $_POST['app_id'], $unregisteredUserID);
		if ($userID!=-1 || $unregisteredUserID!=-1) {
			$userFormID = $userForm->id;
			if (is_writable($uploads_dir)) {
				if (!file_exists($uploads_dir.'/'.$userFormID)) {
					mkdir($uploads_dir.'/'.$userFormID);
				}
				chmod($uploads_dir.'/'.$userFormID, $directory_permissions);
				if (is_writable($uploads_dir.'/'.$userFormID)) {
					$uploads_dir = $uploads_dir.'/'.$userFormID;
				}
			}
		}
	}
}
// If uploader_name has been set, create a sub-directory using this name
if (isset($_POST['uploader_name'])) {
	$uploads_dir = $uploads_dir.'/'.str_replace("/","",$_POST['uploader_name']);
	mkdir($uploads_dir);
	chmod($uploads_dir, $directory_permissions);
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
if (isset($_POST['unique_filename'])) {
	$output['unique_filename'] = $_POST['unique_filename'];
} else {
	$output['unique_filename'] = md5(uniqid(mt_rand())).".".$file_extension;
}



// Back-end file size check for extra security
$total_bytes = 0;
$file_blobs = array();
foreach (scandir($uploads_dir) as $file) {
	if (strpos($file, $output['unique_filename'])!==false) {
		$file_blobs[] = $file;
		$total_bytes+= intval(filesize($uploads_dir.'/'.$file));
	}
}
if ((intval($total_bytes) + intval($_FILES[0]["size"])) > $allowed_bytes) {
	foreach (scandir($uploads_dir) as $file) {
		if (strpos($file, $output['unique_filename'])!==false) {
			unlink($uploads_dir.'/'.$file);
		}
	}
	error_log("Number of bytes in file '".$output['unique_filename']."' exceeds limit");
	$output['status'] = '4';
	echo json_encode($output);
	exit;
}


// Upload the blob
$target_file = $uploads_dir.'/'.$output['unique_filename'].".".$_POST['blob_part'];
error_log("(".$_SERVER['REMOTE_ADDR'].") ".$target_file);



if (!move_uploaded_file($_FILES[0]["tmp_name"], $target_file)) {
	error_log("(".$_SERVER['REMOTE_ADDR'].") "."Failed to move "+$_FILES[0]["tmp_name"]+" to "+$target_file);
	$output['status'] = '2';
	echo json_encode($output);
	exit;
}
chmod($target_file, $file_permissions);
$output['status'] = '0';
error_log("(".$_SERVER['REMOTE_ADDR'].") ".json_encode($outputArray));



if ($_POST['blob_part']=="pants") {
	for ($i=1; $i<count($file_blobs); $i++) {
		if (!file_exists($uploads_dir.'/'.$output['unique_filename'].'.'.$i)) {
			error_log("File blob '".$uploads_dir.'/'.$output['unique_filename'].'.'.$i."' not found");
			$output['status'] = '5';
			$output['error'] = "File blob '".$uploads_dir.'/'.$output['unique_filename'].'.'.$i."' not found";
			echo json_encode($output);
			exit;
		}
		file_put_contents($uploads_dir.'/'.$output['unique_filename'].'.0', file_get_contents($uploads_dir.'/'.$output['unique_filename'].'.'.$i), FILE_APPEND);
		unlink($uploads_dir.'/'.$output['unique_filename'].'.'.$i);
	}
	file_put_contents($uploads_dir.'/'.$output['unique_filename'].'.0', file_get_contents($uploads_dir.'/'.$output['unique_filename'].'.pants'), FILE_APPEND);
	unlink($uploads_dir.'/'.$output['unique_filename'].'.pants');
	rename($uploads_dir.'/'.$output['unique_filename'].'.0', $uploads_dir.'/'.$output['unique_filename']);
}









echo json_encode($output);




