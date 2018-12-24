<?php
include_once '/var/www/jadu/public_html/site/custom_scripts/repo/logs/RBGerror_log.php';
set_error_log(__FILE__);

session_start();
include_once("JaduConstants.php");
include_once("xforms2/JaduXFormsUserForms.php");

/**
 * Zips the files relating to a user's instance of the application's uploader
 * @param $app_id The ID given by the uploader during uploads, will look for a formID if not set
 * @param $unique_id The unique ID given by the uploader during uploads, will look for a userFormID if not set
 * @return The successfully created zip file, or false
 */
function zip_uploaded_files($app_id, $unique_id) {

	// Set the directory to be zipped
	$target_dir = '/var/www/jadu/public_html/site/custom_scripts/repo/apps/file-uploads/uploads/'.$app_id.'/'.$unique_id;
	if (!file_exists($target_dir)) {
		error_log($target_dir." does not exist");
		return false;
	}

	// Prepare the zip file
	$all_files = scandir($target_dir);
	$zip = new ZipArchive();
	if ($zip->open($target_dir.'.zip', file_exists($target_dir.'.zip')?ZIPARCHIVE::OVERWRITE:ZIPARCHIVE::CREATE) !== true) {
		return false;
	}
	foreach ($all_files as $file) {
		if ($file=="." || $file=="..") continue;
		if (is_dir($target_dir.'/'.$file)) {
			// Handle the zipping of this subfolder and its files
			foreach (scandir($target_dir.'/'.$file) as $also_a_file) {
				if ($also_a_file=="." || $also_a_file=="..") continue;
				$zip->addFile($target_dir.'/'.$file.'/'.$also_a_file, $file.'/'.$also_a_file);
			}
		} else {
			// Is a single file, add to zip
			$zip->addFile($target_dir.'/'.$file, $file);
		}
	}
	$zip->close();

	// Check if file was successfully created and return
	if (file_exists($target_dir.'.zip')) {
		chmod($target_dir.'.zip', 0777);
		rm_r($target_dir);
		return $target_dir.'.zip';
	} else {
		return false;
	}
}

/**
 * Recursively remove a directory and all contained files
 * @param $dir The directory to remove
 */
function rm_r($dir) {
	$files = scandir($dir);
	foreach ($files as $file) {
		if ($file=="." || $file=="..") continue;
		if (is_dir($dir.'/'.$file)) {
			rm_r($dir.'/'.$file);
		} else {
			unlink($dir.'/'.$file);
		}
	}
	rmdir($dir);
}

restore_error_log();
