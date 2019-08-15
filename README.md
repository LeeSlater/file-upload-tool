# file-upload-tool #

A flexible web-based file upload tool built in JavaScript and PHP.

## Feature overview ##
- Flexible configuration
- Multiple independant upload fields can be defined and configured with JSON
- Drag and drop capable
- Multiple files can be selected or dragged onto the uploaders at once
- Mistakenly uploaded files can be removed by the user
- Files are uploaded one chunk at a time to reduce the chance of timeout on large file uploads
- Integration with existing web forms
- Optional JSON output into a field for analyses by other scripts
- Easily changed stylesheets

## Setup ##

Download file-upload-tool and store it somewhere on your server that is accessible by the website.

Set the uploads folder (i.e. where the files will be uploaded to) in upload.php and delete.php. Also set the URL of file-upload-tool in include.js.

Include the include.js file via HTML script tag, like so:
```
<script src='/path/to/file-upload-tool/include.js'></script>
```

Define the element with the ID 'file-uploaders', which will be the default element for uploaders to be placed into:
```
<div id='file-uploaders'></div>
```

Next, use JSON to define the individual uploaders and their properties, before calling generate_uploaders() to create them:
```
<script>
	var uploaders = [
		{
			/* Uploader 1 */
			"label": "fire safety certificate",
			"extensions": "pdf,jpg,jpeg,doc,docx,odt,txt,png",
			"required": "true",
		},
		{
			/* Uploader 2 */
			"label": "additional files",
		}
	];
	window.onload = (function(){
		generate_uploaders(uploaders[, settings]);
	});
</script>
```

The generate_uploaders() accepts two parameters:
- uploaders: Required. A string of JSON where each object represents an individual uploader and its properties.
- settings: Optional. An object containing the global settings for the uploader.


Available settings properties:

Parameter              | Description
-----------------------|---------------
submit_button_selector | Optional. A CSS-style selector to find a form submission button, which will then be replaced (visually) with a validation button to check required uploaders.
output_field_selector  | Optional. If you wish to output the JSON-encoded results to an input or text field, use a CSS-style selector to define the field here. The uploaders will also read this field on loading to keep track of past activities (e.g. upon going 'back' to this page on a multi-page online form)
stylesheet			   | Optional. The file name of an alternative stylesheet inside file-upload-tool/stylesheets/


Available uploader object properties:

Property Name           | Description
------------------------|------------------------
label                   | Defaults to 'files'. A string describing the files to be uploaded. This will be shown on the uploader's label, and spaces will be replaced with underscores for directory names during upload.
app_id                  | \* Optional. A string that will be used when uploading files to define a directory for the app. For example if you have several apps using this uploader, the name of the app could be here.
unique_id               | \* Optional. A string which should be unique for every instance. In a form for example, this may be the user's form ID.
allowed_uploads         | If there is a limit on how many files can be submitted for the uploader, set it here. If 0 or undefined, no limit is set.
extensions              | If there are a limited number of extensions the user is allowed to upload, define them in a comma-separated list. If blank or undefined, defaults to 'pdf,jpg,jpeg,png,doc,docx,odt,txt'.
required                | \*\* If at least one file is required uploading, set to true. If false or undefined, not required. Can be set to the element selector for a checkbox (e.g. #id), where checked will equate to true.
required_element_mode   | If 'required' is an element selector, this option will allow you to define how the element should be used. This is intended for future development and only currently supports 'checkbox', which is used by default regardless.
size_limit              | A number defined in bytes (10000000 = 10mb) that is the maximum size of each file. Defaults to 23mb.
parent_element          | If defined (via a CSS-style selector) the uploader will be placed in this element, instead of the default #file-uploaders element.

\*   The final file structure for an upload should be `uploads/<app_id>/<unique_id>/<uploader_label>/<files>`.

\*\* Required upload fields require the `output_field_selector` to be defined, so that a validation button can be placed.


Since this upload tool is entirely JavaScript reliant, you may wish to warn users that do not have JavaScript enabled:
```
<noscript>
	<div id="noscript-warning" style="background-color: #f5f5f5; border: 2px solid #f00; padding: 0px 10px;  max-width: 500px;">
		<h3>JavaScript required</h3>
		<p>Sorry, this file upload tool requires JavaScript to be running. Please check that you have an up-to-date browser and that your settings allow JavaScript to run.</p>
	</div>
</noscript>
```

## File overview ##

**include.js**
JavaScript code to generate the uploaders' HTML, pull in the correct CSS and control the user experience when using the uploaders

**class.file_manager.php**
Contains server-side functions for file management such as blob-merging, file deletion and upload path preparation.

**upload.php**
Called by the AJAX in include.php, this calls the function in class.file_manager.php responsible for handling the saving of the files into the right place in the file system.

**delete.php**
Called by the AJAX in include.php, this calls the function in class.file_manager.php responsible for handling the deletion of user-selected files.

**stylesheets/**
Storage for the stylesheets. 'default.css' is loaded unless the filename of another is specified in settings.

