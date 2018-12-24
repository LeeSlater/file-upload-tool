<?php
include_once '/var/www/jadu/public_html/site/custom_scripts/repo/logs/RBGerror_log.php';
set_error_log(__FILE__);


session_start();
$new_file_names = array();
$errors = array();


/**
 * Set a more specific upload directory by testing for an app_id in $_POST, and then
 * checking to see if there is an incomplete XForm that matches the app_id and $_SESSION variables.
 */
$uploads_root = "/var/www/jadu/public_html/site/custom_scripts/repo/apps/file-uploads/uploads";
$uploads_dir = $uploads_root.'/general';
if (isset($_POST['app_id'])) {
	// App ID is set in $_POST variable, change uploads directory to relevant file
	if (is_writable($uploads_root)) {
		if (!file_exists($uploads_root.'/'.$_POST['app_id'])) {
			mkdir($uploads_root.'/'.$_POST['app_id']);
		}
		chmod($uploads_root.'/'.$_POST['app_id'], 0777);
		if (is_writable($uploads_root.'/'.$_POST['app_id'])) {
			$uploads_dir = $uploads_root.'/'.$_POST['app_id'];
		}
	}
	if (isset($_POST['unique_id'])) {
		// Unique ID is set in $_POST variable, change uploads directory to unique sub-directory within the app's uploads directory
		if (is_writable($uploads_dir)) {
			if (!file_exists($uploads_root.'/'.$_POST['app_id'])) {
				mkdir($uploads_root.'/'.$_POST['app_id']);
			}
			chmod($uploads_dir.'/'.$_POST['unique_id'], 0777);
			if (is_writable($uploads_dir.'/'.$_POST['unique_id'])) {
				$uploads_dir = $uploads_dir.'/'.$_POST['unique_id'];
			}
		}
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
				chmod($uploads_dir.'/'.$userFormID, 0777);
				if (is_writable($uploads_dir.'/'.$userFormID)) {
					$uploads_dir = $uploads_dir.'/'.$userFormID;
				}
			}
		}
	}
}

if (isset($_POST['uploader_name'])) {
	// uploader_name has been set, create another uploads directory using this name
	if (is_writable($uploads_dir)) {
		mkdir($uploads_dir.'/'.str_replace("/","",$_POST['uploader_name']));
		chmod($uploads_dir.'/'.str_replace("/","",$_POST['uploader_name']), 0777);
		$uploads_dir = $uploads_dir.'/'.str_replace("/","",$_POST['uploader_name']);
	}
}


/**
 * Final check to make sure that we can write to the uploads directory
 */
if (!file_exists($uploads_dir) || !is_writable($uploads_dir)) {
	error_log("(".$_SERVER['REMOTE_ADDR'].") "."Cannot write files to $uploads_dir");
	$errors[] = "An error occurred";
	$outputArray = array('success' => false, 'errors' => $errors, 'new_file_names' => json_encode($new_file_names));
	echo json_encode($outputArray);
	exit();
} else {
	error_log("Final uploads directory: ".$uploads_dir);
}


/**
 * Loop through all files submitted to this script, make any final checks
 * then move them into their final positions
 */
for ($i=0; $i<count($_FILES); $i++) {

	error_log($_FILES[$i]["name"]);

	// Check if the file extension used by this file is allowed
	$file_extension = pathinfo($uploads_dir."/".$_FILES[$i]["name"],PATHINFO_EXTENSION);
	if (isset($_POST['extensions'])) {
		$mime_allowed = false;
		$allowed_extensions = explode(",",$_POST['extensions']);
		foreach($allowed_extensions as $ext) {
			if (strtolower($ext)==strtolower($file_extension)) {
				$mime_allowed = true;
			}
		}
		if ($mime_allowed==false) {
			error_log("(".$_SERVER['REMOTE_ADDR'].") File extension '".$file_extension."' does not match ".$_POST['extensions']);
			$errors[] = "Unsupported file extension (Use ".$_POST['extensions'].")";
			$outputArray = array('success' => false, 'errors' => $errors, 'new_file_names' => json_encode($new_file_names));
			echo json_encode($outputArray);
			exit();
		}
	}

	// Move the files into position
	$new_file_name = md5(uniqid(mt_rand())).".".$file_extension;
	$target_file = $uploads_dir.'/'.$new_file_name;
	error_log("(".$_SERVER['REMOTE_ADDR'].") ".$target_file);
	if (file_exists($_FILES[$i]["tmp_name"])) {
		error_log("(".$_SERVER['REMOTE_ADDR'].") "."File exists");
	}
	if (move_uploaded_file($_FILES[$i]["tmp_name"], $target_file)) {
		chmod($target_file, 0666);
		$new_file_names[] = $new_file_name;
		$outputArray = array('success' => true, 'errors' => $errors, 'new_file_names' => json_encode($new_file_names));
		error_log("(".$_SERVER['REMOTE_ADDR'].") ".json_encode($outputArray));
		echo json_encode($outputArray);
		exit();
	} else {
		error_log("(".$_SERVER['REMOTE_ADDR'].") "."Failed to move "+$_FILES[$i]["tmp_name"]+" to "+$target_file);
		$errors[] = basename($_FILES[$i]["name"])." could not be uploaded";
		$outputArray = array('success' => false, 'errors' => $errors, 'new_file_names' => json_encode($new_file_names));
		echo json_encode($outputArray);
		exit();
	}

}

restore_error_log();
