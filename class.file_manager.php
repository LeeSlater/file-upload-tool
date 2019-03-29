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
 * A class for controlling files on the server, i.e.
 * - Handling the creation of additional directories
 * - Handling the server-side upload of each file blob
 * - Merging those blobs back into the original file
 * - Deleting files
 */

class file_manager {

	/**
	 * The absolute path to your 'uploads' directory.
	 * Note: This script should have the necessary privileges to modify the contents of this folder.
	 */
	public $uploads_root = "/var/www/site/public_html/custom/file-uploads/uploads";

	// Set default uploads directory
	public $uploads_dir;
	
	/**
	 * Per-file (i.e. all chunks combined for one file) size limit. This is more for security purposes to prevent malicious
	 * attacks from completely filling your server's hard drive. This should be kept higher than the official size limit, 
	 * which should be set in the JavaScript for better handling.
	 */
	private $allowed_bytes = 100000000; // 100MB
	
	// File permissions for directories and files generated by this script
	public $directory_permissions = 0777;
	public $file_permissions = 0666;


	function __construct() {
		$this->uploads_dir = $this->uploads_root;
	}


	/**
	 * Checks that any files in $uploads_dir under the heirarchy of $uploads_root
	 * have been created and set to the correct privileges
	 */
	public function prepare_uploads_dir() {
		if (!is_writable($this->uploads_root)) {
			error_log("(".$_SERVER['REMOTE_ADDR'].") Cannot write files to ".$this->uploads_root);
			$output['status'] = "1a";
			echo json_encode($output);
			exit;
		}
		$extra_files = explode("/", substr($this->uploads_dir, strlen($this->uploads_root)));
		$path = $this->uploads_root;
		foreach ($extra_files as $file) {
			if (!file_exists($path."/".$file)) {
				mkdir($path."/".$file);
				chmod($path."/".$file, $directory_permissions);
				$path = $path."/".$file;
			}
		}
	}

	
	/**
	 * Upload the blob, and if it's the last blob for the file continue to merge them into one
	 */
	public function upload_blob() {
		// Error handling
		if (!is_writable($this->uploads_root)) {
			error_log("(".$_SERVER['REMOTE_ADDR'].") Cannot write files to ".$this->uploads_root);
			$output['status'] = "1a";
			echo json_encode($output);
			exit;
		}
		if (!file_exists($this->uploads_dir) || !is_writable($this->uploads_dir)) {
			error_log("(".$_SERVER['REMOTE_ADDR'].") Cannot write files to uploads_dir");
			$output['status'] = '1b';
			echo json_encode($output);
			exit();
		}

		error_log("Blob being uploaded: ".$_POST['blob_name']);

		// Set the unique filename for server storage
		$file_extension = pathinfo($this->uploads_dir."/".$_POST['blob_name'], PATHINFO_EXTENSION);
		if (isset($_POST['unique_filename'])) {
			$output['unique_filename'] = $_POST['unique_filename'];
		} else {
			$output['unique_filename'] = md5(uniqid(mt_rand())).".".$file_extension;
		}


		// Back-end file size check for extra security
		$total_bytes = 0;
		$file_blobs = array();
		foreach (scandir($this->uploads_dir) as $file) {
			if (strpos($file, $output['unique_filename'])!==false) {
				$file_blobs[] = $file;
				$total_bytes+= intval(filesize($this->uploads_dir.'/'.$file));
			}
		}
		if ((intval($total_bytes) + intval($_FILES[0]["size"])) > $this->allowed_bytes) {
			foreach (scandir($this->uploads_dir) as $file) {
				if (strpos($file, $output['unique_filename'])!==false) {
					unlink($this->uploads_dir.'/'.$file);
				}
			}
			error_log("Number of bytes in file '".$output['unique_filename']."' exceeds limit");
			$output['status'] = '4';
			echo json_encode($output);
			exit;
		}


		// Upload the blob
		$target_file = $this->uploads_dir.'/'.$output['unique_filename'].".".$_POST['blob_part'];
		error_log("(".$_SERVER['REMOTE_ADDR'].") Target file is: ".$target_file);


		if (!move_uploaded_file($_FILES[0]["tmp_name"], $target_file)) {
			error_log("(".$_SERVER['REMOTE_ADDR'].") "."Failed to move "+$_FILES[0]["tmp_name"]+" to "+$target_file);
			$output['status'] = '2';
			echo json_encode($output);
			exit;
		}
		chmod($target_file, $this->file_permissions);
		$output['status'] = '0';
		error_log("(".$_SERVER['REMOTE_ADDR'].") ".json_encode($output));


		// If this is the last blob, write the contents of all files into one
		if ($_POST['blob_part']=="pants") {
			for ($i=1; $i<count($file_blobs); $i++) {
				if (!file_exists($this->uploads_dir.'/'.$output['unique_filename'].'.'.$i)) {
					error_log("File blob '".$this->uploads_dir.'/'.$output['unique_filename'].'.'.$i."' not found");
					$output['status'] = '5';
					$output['error'] = "File blob '".$this->uploads_dir.'/'.$output['unique_filename'].'.'.$i."' not found";
					echo json_encode($output);
					exit;
				}
				file_put_contents($this->uploads_dir.'/'.$output['unique_filename'].'.0', file_get_contents($this->uploads_dir.'/'.$output['unique_filename'].'.'.$i), FILE_APPEND);
				unlink($this->uploads_dir.'/'.$output['unique_filename'].'.'.$i);
			}
			file_put_contents($this->uploads_dir.'/'.$output['unique_filename'].'.0', file_get_contents($this->uploads_dir.'/'.$output['unique_filename'].'.pants'), FILE_APPEND);
			unlink($this->uploads_dir.'/'.$output['unique_filename'].'.pants');
			rename($this->uploads_dir.'/'.$output['unique_filename'].'.0', $this->uploads_dir.'/'.$output['unique_filename']);
		}
		echo json_encode($output);
	}


	/**
	 * Merge the defined file blobs into a single file
	 * Currently unused and would need some work to be used
	 */
	public function merge_blobs($name) {
		$file_blobs = array();
		foreach (scandir($this->uploads_dir) as $file) {
			if (strpos($file, $output['unique_filename'])!==false) {
				$file_blobs[] = $file;
			}
		}
		for ($i=1; $i<count($file_blobs)-1; $i++) {
			if (!file_exists($this->uploads_dir.'/'.$output['unique_filename'].'.'.$i)) {
				error_log("File blob '".$this->uploads_dir.'/'.$output['unique_filename'].'.'.$i."' not found");
				$output['status'] = '5';
				$output['error'] = "File blob '".$this->uploads_dir.'/'.$output['unique_filename'].'.'.$i."' not found";
				echo json_encode($output);
				exit;
			}
			file_put_contents($this->uploads_dir.'/'.$output['unique_filename'].'.0', file_get_contents($this->uploads_dir.'/'.$output['unique_filename'].'.'.$i), FILE_APPEND);
			unlink($this->uploads_dir.'/'.$output['unique_filename'].'.'.$i);
		}
		file_put_contents($this->uploads_dir.'/'.$output['unique_filename'].'.0', file_get_contents($this->uploads_dir.'/'.$output['unique_filename'].'.pants'), FILE_APPEND);
		unlink($this->uploads_dir.'/'.$output['unique_filename'].'.pants');
		rename($this->uploads_dir.'/'.$output['unique_filename'].'.0', $this->uploads_dir.'/'.$output['unique_filename']);
	}


	/**
	 * Delete a file within the upload directories matching the name $file
	 */
	public function delete_file($file) {
		$scanned = $this->scan_dir_for_file($this->uploads_dir, $file);
		if ($scanned==false) {
			return false;
		}
		unlink($scanned);
	}


	/**
	 * Scan the given directory $dir for a file matching the name $file.
	 * If there are subdirectories, recall the function using them as the $dir parameter.
	 * Returns the instance of the file we were searching for, or false
	 */
	public function scan_dir_for_file($dir, $file) {
		$files = scandir($dir);
		foreach ($files as $f) {
			if ($f=="." || $f=="..") continue;
			if (is_dir($dir.'/'.$f)) {
				$scanned_subdir = $this->scan_dir_for_file($dir.'/'.$f, $file);
				if ($scanned_subdir!=false) {
					return $scanned_subdir;
				}
			} else {
				if ($f==$file) {
					return $dir."/".$f;
				}
			}
		}
		return false;
	}


	/**
	 * Cleanup the file uploads area - loop through the heirarchy and delete any unused files
	 * - Delete files (and blobs) older than a certain date (e.g. older than 6 months ago today)
	 * - Delete directories if they are older than a certain date and are empty
	 */
	public function remove_old_files($dir, $file) {
		$files = scandir($dir);
		foreach ($files as $f) {
			if ($f=="." || $f=="..") continue;
			if (is_dir($dir.'/'.$f)) {
				$scanned_subdir = $this->remove_old_files($dir.'/'.$f, $file);
				if ($scanned_subdir!=false) {
					return $scanned_subdir;
				}
			} else {
				// TODO: Replace with date check
				if (false) {
					unlink($file);
				}
			}
		}
		return false;
	}
}
