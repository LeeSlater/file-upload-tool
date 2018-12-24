var submit_button = false;
var output_field = false;
var bypass_validator = false;


/**
 * Generates the HTML for the uploaders based on the parameters given for each one in the uploaders object.
 * Will also generate a validation button if submit_button is defined, set and read the output_field if
 * output_field_selector is defined and valid, then call further sections of the script.
 */
function generate_uploaders(uploaders, submit_button_selector, output_field_selector, stylesheet) {

	// Load the JavaScript logging code before running
	load_script('/site/custom_scripts/repo/utils/javascript-error-logging/log.js', function() {
		set_log_file('apps/file-uploads');
		error_log("Generating "+uploaders.length+" uploader(s)");
		
		// Check the parameters, if they have been set and how to proceed
		if (uploaders=="undefined" || uploaders==undefined || uploaders=="") {
			console.log("Custom uploader info not set. Cancelling uploader generation.");
			error_log("Fatal: Custom uploader info not set. Cancelling uploader generation.");
			return;
		}
		if (output_field_selector!=undefined && output_field_selector!=false && output_field_selector!="") {
			if (document.querySelector(output_field_selector)!=false) {
				output_field = document.querySelector(output_field_selector);
			} else {
				console.log(output_field_selector+" could not be found");
				error_log(output_field_selector+" could not be found");
				return;
			}
		}
		if (submit_button_selector!=undefined && submit_button_selector!=false && submit_button_selector!="") {
			if (document.querySelector(submit_button_selector)!=false) {
				submit_button = document.querySelector(submit_button_selector);
			} else {
				console.log(submit_button_selector+" could not be found");
				error_log(submit_button_selector+" could not be found");
				return;
			}
		}
		if (stylesheet!=undefined && stylesheet!=false && stylesheet!="") {
			// Load specified stylesheet
			document.head.innerHTML+= "<link rel='stylesheet' type='text/css' href='/site/custom_scripts/repo/apps/file-uploads/uploader/stylesheets/"+stylesheet+"'>";
		} else {
			// Load default stylesheet
			document.head.innerHTML+= "<link rel='stylesheet' type='text/css' href='/site/custom_scripts/repo/apps/file-uploads/uploader/stylesheets/default.css'>";
		}
		document.head.innerHTML+= "<link rel='stylesheet' href='https://fonts.googleapis.com/icon?family=Material+Icons'>";

		// Loop through the properties of each uploader and add customised HTML for each one
		for (i=0; i<uploaders.length; i++) {
			var uploader_html = "";
			error_log("uploader-"+i+"/"+(uploaders[i].label?uploaders[i].label:"Unnamed uploader")+"\nAllowed uploads: "+uploaders[i].allowed_uploads+"\nAllowed extensions: "+uploaders[i].extensions+"\nApp ID: "+uploaders[i].app_id);
			var attributes = {};
			var uploader_name = uploaders[i].label.replace(/ /g, "_");
			attributes['files-uploaded'] = 0;
			var initial_file_list_html = "";

			if (output_field!=false) {
				var uploads = JSON.parse(output_field.value);
				if (uploads[uploader_name]!=undefined) {
					attributes['files-uploaded'] = uploads[uploader_name].length;
					for (n=0; n<attributes['files-uploaded']; n++) {
						initial_file_list_html+= "<div id='file_"+uploads[uploader_name][n].new_name.replace(/\./g, "_")+"'><div class='file-name'>[<button type='button' onclick=\"remove_file(\'"+uploader_name+"\',\'"+uploads[uploader_name][n].new_name+"\')\">Remove</button>] "+uploads[uploader_name][n].original_name+"</div><div class='file-status'><span class='material-icons' style='color: green;'>check</span><span class='verbose'>File was successfully uploaded</span></div></div>";
					}
				}
			}

			if (uploaders[i].app_id!=undefined) {
				attributes['app_id'] = uploaders[i].app_id;
			} else if (window.location.pathname.split('/')[1]=="forms" && window.location.pathname.split('/')[2]=="form") {
				attributes['app_id'] = window.location.pathname.split('/')[3];
			}
			error_log("App ID is "+attributes['app_id']);
			if (uploaders[i].unique_id!=undefined) {
				error_log("Unique ID is "+uploaders[i].unique_id);
				attributes['unique_id'] = uploaders[i].unique_id;
			}
			attributes['required'] = (uploaders[i].required=="true" || uploaders[i].required==true)?"yes":"no";
			attributes['allowed-uploads'] = (uploaders[i].allowed_uploads?uploaders[i].allowed_uploads:"");
			attributes['label'] = "Browse&nbsp;for&nbsp;"+(uploaders[i].label?uploaders[i].label:"files");
			attributes['extensions'] = uploaders[i].extensions?uploaders[i].extensions:"pdf,jpg,jpeg,png,doc,docx,odt,xlsx,ods,txt";
			attributes['file-size-limit'] = uploaders[i].size_limit?uploaders[i].size_limit:"23000000";

			var uploader_attributes = "";
			for (var key in attributes) {
				uploader_attributes+= " data-"+key+"='"+attributes[key]+"'";
			}

			// Generate the HTML based on those properties
			uploader_html+= "<div id='uploader-"+i+"' class='uploader'"+uploader_attributes+">";
			uploader_html+= "<span id='status-"+i+"' class='status'></span>";
			uploader_html+= "<div class='label-container'><label for='file-"+i+"'>Browse&nbsp;for&nbsp;"+uploaders[i].label+"</label></div>";
			uploader_html+= "<input id='file-"+i+"' class='file_field' name='files' type='file' multiple/>";
			uploader_html+= "<div id='file-list-"+i+"' class='file-list'>"+initial_file_list_html+"</div>";
			uploader_html+= "<input id='uploader_name-"+i+"' name='uploader_name' class='hidden' type='text' value='"+uploader_name+"' readonly/>";
			uploader_html+="</div>";

			if (uploaders[i].parent_element!=undefined) {
				document.querySelector(uploaders[i].parent_element).innerHTML+=uploader_html;
			} else {
				document.getElementById("file-uploaders").innerHTML+=uploader_html;
			}

		}

		for (i=0; i<uploaders.length; i++) {
			update_status(document.querySelector("#uploader-"+i));
		}

		// If submit_button_selector is defined, create a button in the same parentNode with
		// the same classes and styles applied, then hide the original
		if (submit_button!=false) {
			var submit_style = getComputedStyle(submit_button);
			var validation_style = '';
			for(i=0; i<submit_style.length; i++) {
				validation_style+= submit_style[i] + ':' + submit_style.getPropertyValue(submit_style[i])+';';
			}
			document.getElementsByTagName("head")[0].innerHTML+= "#validation_button {"+validation_style+"}";
			var validation_button = document.createElement("button");
			validation_button.setAttribute("id","validation_button");
			validation_button.setAttribute("class",submit_button.getAttribute("class"));
			validation_button.setAttribute("type","button");
			validation_button.setAttribute("name","next");
			validation_button.setAttribute("onclick","xfp_validate_submit()");
			validation_button.innerHTML = "Next";
			submit_button.parentNode.appendChild(validation_button);
			submit_button.setAttribute("style","display:none;")
		}

		Array.prototype.forEach.call(document.querySelectorAll('.uploader'),function(uploader) {
			manage_uploaders(uploader);
		});
	});
}


/**
 * Called from generate_uploaders() for each uploader
 * Handles drag/drop, class changes for CSS, and calling the upload() function when files are selected or dropped
 */
function manage_uploaders(uploader) {
	var input = uploader.querySelector('.file_field');
	var label = uploader.querySelector('label');
	['drag','dragstart','dragend','dragover','dragenter','dragleave','drop'].forEach( function( event ) {
		document.addEventListener( event, function( e ) {
			e.preventDefault();
			e.stopPropagation();
		});
	});
	['dragover','dragenter'].forEach(function(event) {
		uploader.addEventListener(event, function() {
			uploader.classList.add("drag");
		});
	});
	['dragleave','dragend','drop'].forEach(function(event) {
		uploader.addEventListener(event, function() {
			uploader.classList.remove("drag");
		});
	});
	input.addEventListener("change", function(e) {
		error_log(uploader.querySelector(".file_field").files.length+" file(s) selected on "+uploader.getAttribute("id"));
		handle_uploads(uploader,e);
	});
	uploader.addEventListener("drop", function(e) {
		error_log(e.dataTransfer.files.length+" file(s) dropped on "+uploader.getAttribute("id"));
		handle_uploads(uploader,e);
	});
}


/**
 * Called by manage_uploaders() when a file is selected or dropped
 * Performs some initial checks, then calls upload_file() to kick off the file uploads
 */
function handle_uploads(uploader,e) {

	// I don't think handle_uploads() can be called if the uploader is in progress, but just in case...
	if (uploader.classList.contains("in-progress")) {
		error_log("User attempted to use the uploader while it was already uploading files, request cancelled.");
		uploader.querySelector(".status").innerHTML = "Upload already in progress";
		return;
	}
	if (uploader.classList.contains("disabled")) {
		error_log("User attempted to use the uploader while it was disabled. Request cancelled.");
		return;
	}
	uploader.classList.add("in-progress");

	var status = "";
	var input = uploader.querySelector(".file_field");
	var files;
	var number_of_files = 0;

	// Set the number of files to try to upload, and set files to the file source so it can be generically referred to later
	if (e.dataTransfer) {
		number_of_files+=e.dataTransfer.files.length;
		files = e.dataTransfer.files;
	} else {
		number_of_files+=input.files.length;
		files = input.files;
	}

	// Let the user know an upload is in progress, and log it
	error_log("Uploading "+number_of_files+" files");
	uploader.querySelector("label").innerHTML = "Uploading...";

	// Check if the uploader will go over the number of files allowed, and cancel the upload if so (and tell the user)
	if ((uploader.getAttribute("data-files-uploaded")-0)+number_of_files > uploader.getAttribute("data-allowed-uploads")-0) {
		if ((uploader.getAttribute("data-allowed-uploads")-0)!=0) {
			var error_msg = "Upload cancelled as the file limit was hit: ";
			error_msg+= ", "+uploader.getAttribute("data-files-uploaded")+" files already uploaded"
			error_msg+= ", "+number_of_files+" files to be uploaded";
			error_msg+= ", "+uploader.getAttribute("data-allowed-uploads")+" files allowed";
			error_log(error_msg);
			uploader.querySelector(".status").innerHTML = "Upload cancelled, only "+uploader.getAttribute("data-allowed-uploads")+" file"+((uploader.getAttribute("data-allowed-uploads")-0)==1?"":"s")+" can be uploaded";
			reset_uploader(uploader);
			return;
		}
	}

	// Upload the first file in the files array
	upload_file(uploader,files,0);

}


/**
 * Upload a single file (files[index]) by AJAXing it to a PHP file, then call itself with an
 * increased index if there are more files to send once this file has finished uploading
 * Done this way to reduce traffic to the server upload file and upload one at a time, for stability
 */
function upload_file(uploader,files,index) {

	var file = files[index];
	var uploader_name = uploader.querySelector("input[name=uploader_name]").value;
	error_log("Name: "+file.name+", Size: "+file.size+", Type: "+file.type);

	// Check if the file goes over the size limit, cancel and display an error if so
	if (file.size-0 > uploader.getAttribute("data-file-size-limit")-0) {
		if (index+1 < files.length) {
			upload_file(uploader,files,index+1);
		} else {
			error_log(file.name+": file too large ("+(uploader.getAttribute("data-file-size-limit")/1000000).toFixed(0)+"mb max)");
			uploader.querySelector(".file-list").innerHTML+= "<div><div class='file-name'>"+file.name+"</div><div class='file-status error'>File too large ("+(uploader.getAttribute("data-file-size-limit")/1000000).toFixed(0)+"mb max)</div></div>";
			reset_uploader(uploader);
		}
		return;
	}

	// Create a new FormData and insert the file and its uploader's properties
	var form_data = new FormData();
	form_data.append(0,file);
	form_data.append("extensions",uploader.getAttribute("data-extensions"));
	// form_data.append("app_id",uploader.querySelector("input[name=app_id]").value);
	form_data.append("app_id",uploader.getAttribute("data-app_id"));
	form_data.append("uploader_name",uploader_name);
	// if (uploader.querySelector("input[name=unique_id]")!=null) {
	if (uploader.getAttribute("data-unique_id")!=null && uploader.getAttribute("data-unique_id")!=undefined) {
		// form_data.append("unique_id",uploader.querySelector("input[name=unique_id]").value);
		form_data.append("unique_id",uploader.getAttribute("data-unique_id"));
	}

	// Submit the above FormData via AJAX
	var ajax = new XMLHttpRequest();
	ajax.open("POST", "/site/custom_scripts/repo/apps/file-uploads/uploader/upload.php", true);
	ajax.onload = function() {
		if (ajax.status>=200 && ajax.status<400) {
			// File located and loaded successfully
			error_log("("+ajax.status+") AJAX connection to server was successful");
			response = JSON.parse(ajax.responseText);
			if (response.success==true) {
				var total_number_of_uploads = (uploader.getAttribute("data-files-uploaded")-0)+1;
				var file_name_without_dots = JSON.parse(response.new_file_names)[0].replace(/\./g, "_");
				uploader.querySelector(".file-list").innerHTML+= "<div id='file_"+file_name_without_dots+"'><div class='file-name'>[<button type='button' onclick=\"remove_file(\'"+uploader.querySelector("input[name=uploader_name]").value+"\',\'"+JSON.parse(response.new_file_names)[0]+"\')\">Remove</button>] "+file.name+"</div><div class='file-status'><span class='material-icons' style='color: green;'>check</span><span class='verbose'>File was successfully uploaded</span></div></div>";
				uploader.setAttribute("data-files-uploaded",total_number_of_uploads);
				var uploader_name = uploader.querySelector("input[name=uploader_name]").value;
				if (output_field!=false) {
					var uploads = JSON.parse(output_field.value);
					var new_object = {};
					new_object['original_name'] = file.name;
					new_object['new_name'] = JSON.parse(response.new_file_names)[0];
					if (!(typeof uploads[uploader_name]=="undefined" || uploads[uploader_name]==null)) {
						uploads[uploader_name] = uploads[uploader_name].concat(new_object);
					} else {
						uploads[uploader_name] = [new_object];
					}
				}
				output_field.value = JSON.stringify(uploads);
				update_status(uploader);
			} else {
				uploader.querySelector(".file-list").innerHTML+= "<div><div class='file-name'>"+file.name+"</div><div class='file-status error'><span class='material-icons' onclick=\"alert('"+response.errors[0]+"')\" style='color: #c1002b;'>error</span><span class='verbose'>"+response.errors[0]+"</span></div></div>";
			}
			if (index+1 < files.length) {
				upload_file(uploader,files,index+1);
			} else {
				reset_uploader(uploader);
			}
		} else {
			// Failure to find or load the file
			error_log("("+ajax.status+") AJAX connection to the server failed");
			if (ajax.status==408) {
				// Let the user know that their request timed out
				uploader.querySelector(".file-list").innerHTML+= "<div><div class='file-name'>"+file.name+"</div><div class='file-status error'><span class='material-icons' onclick=\"alert('Upload request timed out')\" style='color: #c1002b;'>error</span><span class='verbose'>Upload request timed out</span></div></div>";
			} else {
				uploader.querySelector(".file-list").innerHTML+= "<div><div class='file-name'>"+file.name+"</div><div class='file-status error'><span class='material-icons' onclick=\"alert('Connection to the server was unsuccessful')\" style='color: #c1002b;'>error</span><span class='verbose'>Connection to the server was unsuccessful</span></div></div>";
			}
			// Add to the data-files-uploaded attribute anyway in order to bypass the validation, preventing users from being stuck on the page by bugs
			if (bypass_validator==false) {
				alert("Unfortunately, one of your files has not uploaded properly. You can try again, but if this doesn’t work we may need to contact you to obtain a copy of the file.\n\nPlease continue to upload any other files requested before continuing through the form.");
				bypass_validator = true;
			}
			if (index+1 < files.length) {
				upload_file(uploader,files,index+1);
			} else {
				reset_uploader(uploader);
			}
		}
	}
	ajax.onerror = function() {
		error_log("("+ajax.status+") An undefined AJAX error occurred during file upload");
		// Add to the data-files-uploaded attribute anyway in order to bypass the validation, preventing users from being stuck on the page by bugs
		if (bypass_validator==false) {
			alert("Unfortunately, one of your files has not uploaded properly. You can try again, but if this doesn’t work we may need to contact you to obtain a copy of the file.\n\nPlease continue to upload any other files requested before continuing through the form.");
			bypass_validator = true;
		}
		uploader.querySelector(".file-list").innerHTML+= "<div><div class='file-name'>"+file.name+"</div><div class='file-status error'><span class='material-icons' onclick=\"alert('Connection to the server was unsuccessful')\" style='color: #c1002b;'>error</span><span class='verbose'>Connection to the server was unsuccessful</span></div></div>";
		if (index+1 < files.length) {
			upload_file(uploader,files,index+1);
		} else {
			reset_uploader(uploader);
		}
	}
	ajax.send(form_data);
}


/**
 * Reset defined uploader
 */
function reset_uploader(uploader) {
	if (uploader.classList.contains("validation_failed")) {
		uploader.classList.remove("validation_failed");
	}
	uploader.classList.remove("in-progress");
	uploader.querySelector("label").innerHTML = uploader.getAttribute("data-label");
}


/**
 * If the submit_button has been defined, validate that the required uploaders have been used
 * and then click the original button to progress
 * - If an uploader has an upload in progress, it will warn the user and ask for confirmation
 * - If network errors have been encountered, the user will be allowed to continue
 */
function xfp_validate_submit() {
	var uploaders = document.querySelectorAll(".uploader");
	var validation_passed = true;
	var upload_in_progress = false;

	for (i=0; i<uploaders.length; i++) {
		if (uploaders[i].getAttribute("data-required")=="yes") {
			if (uploaders[i].classList.contains("in-progress")) {
				upload_in_progress = true;
			}
			if ((uploaders[i].getAttribute("data-files-uploaded")-0) == 0) {
				validation_passed = false;
				uploaders[i].classList.add("validation_failed");
			} else {
				if (uploaders[i].classList.contains("validation_failed")) {
					uploaders[i].classList.remove("validation_failed");
				}
			}
		}
	}

	if (bypass_validator==true) {
		submit_button.click();
		return;
	}

	if (validation_passed==true) {
		if (upload_in_progress==true) {
			if (confirm("We have detected that some files are still uploading.\n\nIf you continue, these files may not be uploaded. Do you wish to continue?")) {
				submit_button.click();
			}
		} else {
			submit_button.click();
		}
	}
}


/**
 * Update the uploader's status according to how many files have been uploaded, if
 * the field is required, how many files can be uploaded, etc
 */
function update_status(uploader) {
	if (uploader.getAttribute("data-required")=="yes") {
		// Required
		if (uploader.getAttribute("data-allowed-uploads")==0 || uploader.getAttribute("data-allowed-uploads")==undefined) {
			// Unlimited files
			uploader.querySelector(".status").innerHTML = "You must upload at least 1 file here";
		} else {
			// File limit
			if (uploader.getAttribute("data-files-uploaded")==uploader.getAttribute("data-allowed-uploads")) {
				uploader.querySelector(".status").innerHTML = "You have reached the file limit on this upload tool";
				if (!uploader.classList.contains("disabled")) {
					uploader.classList.add("disabled");
				}
			} else {
				if (uploader.classList.contains("disabled")) {
					uploader.classList.remove("disabled");
				}
				if (uploader.getAttribute("data-allowed-uploads")==1) {
					uploader.querySelector(".status").innerHTML = "You must upload 1 file here";
				} else {
					uploader.querySelector(".status").innerHTML = "You must upload up to "+uploader.getAttribute("data-allowed-uploads")+" files here";
				}
			}
		}
	} else {
		// Not required
		if (uploader.getAttribute("data-allowed-uploads")==0 || uploader.getAttribute("data-allowed-uploads")==undefined) {
			// Unlimited files
			uploader.querySelector(".status").innerHTML = "";
		} else {
			// File limit
			if (uploader.getAttribute("data-files-uploaded")==uploader.getAttribute("data-allowed-uploads")) {
				uploader.querySelector(".status").innerHTML = "You have reached the file limit on this upload tool";
				if (!uploader.classList.contains("disabled")) {
					uploader.classList.add("disabled");
				}
			} else {
				if (uploader.classList.contains("disabled")) {
					uploader.classList.remove("disabled");
				}
				if (uploader.getAttribute("data-allowed-uploads")==1) {
					uploader.querySelector(".status").innerHTML = "You may upload 1 file here";
				} else {
					uploader.querySelector(".status").innerHTML = "You may upload up to "+uploader.getAttribute("data-allowed-uploads")+" files here";
				}
			}
		}
		
	}
}


/**
 * Calls a PHP file to delete the file from the server, removes mention of the file from the output_field,
 * and removes it from the uploader's file list
 */
function remove_file(uploader_name, file_name) {
	document.querySelector("#file_"+file_name.replace(/\./g, "_")).outerHTML = "";
	var uploader_names = document.querySelectorAll("input[name=uploader_name]");
	var uploader;
	for (i=0; i<uploader_names.length; i++) {
		if (uploader_names[i].value==uploader_name) {
			uploader = uploader_names[i].parentNode;
		}
	}
	var data = new FormData();
	// data.append("app_id",uploader.querySelector("input[name=app_id]").value);
	data.append("app_id",uploader.getAttribute("data-app_id"));
	data.append("file_name",file_name);
	var ajax = new XMLHttpRequest();
	ajax.open("POST", "/site/custom_scripts/repo/apps/file-uploads/uploader/delete.php", true);
	ajax.onload = function() {
		if (ajax.status<200 || ajax.status>400) {
			error_log("Unable to find delete.php: "+ajax.status);
		}
	}
	ajax.onerror = function() {
		error_log("Unable to delete file "+file_name+", ajax error occurred");
	}
	ajax.send(data);

	if (output_field!=false) {
		var output_array = JSON.parse(output_field.value);
		if (output_array[uploader_name]) {
			for (i=0; i<output_array[uploader_name].length; i++) {
				if (output_array[uploader_name][i].new_name==file_name) {
					output_array[uploader_name].splice(i,1);
				}
			}
		}
		output_field.value = JSON.stringify(output_array);
	}

	uploader.setAttribute('data-files-uploaded',uploader.getAttribute('data-files-uploaded')-1);
	update_status(uploader);

}


/**
 * load_script() function lets us include another JavaScript file, then once it loads initiate code based on it.
 * Used to load javascript-error-logging before resuming uploader generation
 */
function load_script(src, f) {
	var head = document.getElementsByTagName("head")[0];
	var script = document.createElement("script");
	script.src = src;
	var done = false;
	script.onload = script.onreadystatechange = function() { 
		// attach to both events for cross browser finish detection:
		if ( !done && (!this.readyState ||
		this.readyState == "loaded" || this.readyState == "complete") ) {
			done = true;
			if (typeof f == 'function') f();
			script.onload = script.onreadystatechange = null;
		}
	};
	head.appendChild(script);
}
