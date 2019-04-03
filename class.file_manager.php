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

	
	function __construct() {

		/**
		 * The absolute path to your 'uploads' directory, where all uploads by this tool are stored.
		 * Note: This script should have the necessary privileges to modify the contents of this folder.
		 */
		$this->uploads_root = "/var/www/jadu/public_html/site/custom_scripts/repo/apps/file-uploads/uploads";
		
		/**
		 * Set default uploads directory
		 */
		$this->uploads_dir = $this->uploads_root;

		/**
		 * Per-file size limit. This is more for security purposes to prevent malicious
		 * attacks from completely filling your server's hard drive.
		 * This should be kept higher than the official size limit(s), 
		 * which should be set in the JavaScript for better handling on a case-by-case basis.
		 */
		$this->allowed_bytes = 100000000; // 100MB
	
		/**
		 * File permissions for directories and files generated by this script
		 */
		$this->directory_permissions = 0777;
		$this->file_permissions = 0666;

		/**
		 * Variables for the cleanup() function
		 * - file_cutoff: A Date() variable. Any files created before this date will be removed.
		 * 		Set to false to not remove any files.
		 * - blob_cutoff: A Date() variable.
		 * 		Any file blobs (files matching the filename used by uploader blobs) created before
		 * 		this date will be removed. Set to false to not remove any blobs.
		 * - dirs: true to delete empty directories within the uploads directory, false will not
		 */
		$this->cleanup_file_cutoff = strtotime("-365 day");
		$this->cleanup_blob_cutoff = strtotime("-7 day");
		$this->cleanup_dirs = true;

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
			if ($file=="") continue;
			if (!file_exists($path."/".$file)) {
				mkdir($path."/".$file);
				chmod($path."/".$file, $this->directory_permissions);
			}
			$path = $path."/".$file;
		}
	}

	
	/**
	 * Upload the blob, and if it's the last blob for the file continue to merge them into one
	 */
	public function upload_blob() {
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
	 * Check if directory is empty or only contains directories
	 */
	public function dir_is_empty($dir) {
		$files = scandir($dir);
		foreach ($files as $f) {
			if ($f=="." || $f=="..") continue;
			if (is_dir($dir.'/'.$f)) {
				// Scan sudirectory directory and return false if it is not empty
				if ($this->dir_is_empty($dir.'/'.$f)==false) {
					return false;
				}
			} else {
				// Not a directory, return false
				return false;
			}
		}
		// Not returned previously, must be empty
		return true;
	}


	/**
	 * Cleanup the file uploads area - loop through the heirarchy and delete any unused files
	 * - Delete files (and blobs) older than a certain date (e.g. older than 6 months ago today)
	 * - Delete empty directories
	 */
	public function cleanup($dir) {
		$files = scandir($dir);
		foreach ($files as $f) {
			if ($f=="." || $f=="..") continue;
			if (is_dir($dir.'/'.$f)) {
				// Tidy files and subdirectories
				$this->cleanup($dir.'/'.$f);
				if ($this->cleanup_dirs==false) continue;
				if ($this->dir_is_empty($dir.'/'.$f)) {
					rmdir($dir.'/'.$f);
				}
			} else {
				// Delete file if older than the cutoff
				if (preg_match('/(^.*)\.([0-9]|pants)$/',$f)) {
					// Blob
					if ($this->cleanup_blob_cutoff==false) continue;
					if (filemtime($dir.'/'.$f) < $this->cleanup_blob_cutoff) {
						unlink($dir.'/'.$f);
					}
				} else {
					// File
					if ($this->cleanup_file_cutoff==false) continue;
					if (filemtime($dir.'/'.$f) < $this->cleanup_file_cutoff) {
						unlink($dir.'/'.$f);
					}
				}
			}
		}
	}


}


