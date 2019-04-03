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


session_start();

require_once("class.file_manager.php");
$file_manager = new file_manager();


// Error handling
if (!isset($_POST['blob_part'])) {
	error_log("(".$_SERVER['REMOTE_ADDR'].") "."Unable to upload blob: blob_part not set");
	$output['status'] = "3";
	echo json_encode($output);
	exit;
}


// Set a more specific upload directory by testing for an app_id an unique_id in $_POST
if (isset($_POST['app_id'])) {
	$file_manager->uploads_dir = $file_manager->uploads_root.'/'.$_POST['app_id'];
	if (isset($_POST['unique_id'])) {
		$file_manager->uploads_dir = $file_manager->uploads_dir.'/'.$_POST['unique_id'];
	} else {
		// Unique ID not manually set, check for userFormID to use as unique ID
		include_once("JaduConstants.php");
		include_once("xforms2/JaduXFormsUserForms.php");
		$userID = isset($_SESSION['userID']) ? $_SESSION['userID'] : -1;
		$unregisteredUserID = isset($_SESSION['unregisteredUserID']) ? $_SESSION['unregisteredUserID'] : -1;
		$userForm = getIncompleteFormIfExistsForUser($userID, $_POST['app_id'], $unregisteredUserID);
		if ($userID!=-1 || $unregisteredUserID!=-1) {
			$userFormID = $userForm->id;
			$file_manager->uploads_dir = $file_manager->uploads_dir.'/'.$userFormID;
		}
	}
}
// If uploader_name has been set, create a sub-directory using this name
if (isset($_POST['uploader_name'])) {
	$file_manager->uploads_dir = $file_manager->uploads_dir.'/'.str_replace("/","",$_POST['uploader_name']);
}


$file_manager->prepare_uploads_dir();

$file_manager->upload_blob();


error_log("Final uploads directory: ".$file_manager->uploads_dir);

