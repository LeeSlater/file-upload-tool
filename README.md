# file-uploads #

This app can be included (details below) from any page on the website in order to generate uploaders, which the user can then utilise to upload files into the directory structure under repo/apps/file-uploads/uploads/.


## File overview ##

**uploads/**
This is where the files will be uploaded to, usually categorised by formID and userFormID in an xform, replacements for these can optionally be defined if not being used on an xform.

**uploader/include.js**
JavaScript code to generate the uploaders' HTML, pull in the correct CSS and control the user experience when using the uploaders

**uploader/upload.php**
Called by the AJAX in include.php, this handles the saving of the files into the right place in the file system.

**uploader/delete.php**
Called by the AJAX in include.php, this handles the deletion of user-selected files.

**uploader/stylesheets/**
Storage for the stylesheets. 'default.css' is loaded unless the filename of another is specified.

**uploader/zipUploadsAction.php**
Is included in Jadu's Actions, and if selected for use within an xform will call function in zip-uploads.php to zip the uploads related to a userFormID

**uploader/zip-uploads.php**
Contains a function to zip uploads related to a unique_id (userFormID on XForms)


## Setting up ##

First, include the include.js file via HTML script src, like so:
```
<script src='/site/custom_scripts/repo/apps/file-uploads/include.js'></script>
```

Optionally (see the parent_element parameter below) define the #file-uploaders element, which will be the default element for uploaders to be placed into:
```
<div id='file-uploaders'></div>
```

Next, use a JavaScript object to define the individual uploaders and their properties, before calling generate_uploaders() to create them:
(If the uploaders object is not defined, the script will fail and log a 'Fatal' error to trigger the log mailer)
```
<script>
	var uploaders = [
		{
			label: 'fire safety certificate',
			extensions: 'pdf,jpg,jpeg',
			required: 'true'
		},
		{
			label: 'additional files'
		}
	];
	generate_uploaders(uploaders[, submit_button_selector][, output_field_selector][, stylesheet]);
</script>
```

The generate_uploaders() parameters are:

Parameter              | Description
-----------------------|---------------
uploaders              | Required. An array of objects. Each object represents an individual upoader and has its own properties.
submit_button_selector | Optional. A CSS-style selector to find a form submission button, which will then be
                       | replaced (visually) with a validation button to check required uploaders.
output_field_selector  | Optional. If you wish to output the results to a field, use a CSS-style selector to do so.
                       | The uploaders will also use this field to keep track of past activities (e.g. on an XForm)
stylesheet			   | The file name of an alternative stylesheet inside file-uploads/uploader/stylesheets/


The possible uploader object properties that you can include in each uploader are as follows:

Property Name   | Description
----------------|--------------- 
label           | Defaults to 'files'. A string describing the files to be uploaded.
                | This will be shown on the uploader's label, and spaces will be replaced with underscores for directory names.
app_id          | \* A string that will be used when uploading files to define a directory for the app.
                | If undefined and in an XForm, the formID will be taken from the session for use instead.
unique_id       | \* A string which should be unique for every instance, if undefined and in an XForm, the userFormID will be used.
allowed_uploads | If there is a limit on how many files can be submitted for the uploader, set it here. If 0 or undefined, no limit is set.
extensions      | If there are a limited number of extensions the user is allowed to upload, define them in a comma-separated list
                | If blank or undefined, defaults to 'pdf,jpg,jpeg,png,doc,docx,odt,txt'
required        | \*\* If at least one file is required uploading, set to true. If false or undefined, not required.
size_limit      | A number defined in bytes (10000000 = 10mb) that is the maximum size of each file.
                | Defaults to 23mb (just under the current server limit of 24mb).
parent_element  | If defined (via a CSS-style selector) the uploader will be placed in this element, instead of the default #file-uploaders

\*   The final file structure for an upload should be `uploads/<app_id>/<unique_id>/<uploader_label>/<files>`.

\*\* Required upload fields require the `output_field_selector` to be defined, so that a validation button can be placed.


Since this upload tool is entirely JavaScript reliant, we should warn users that do not have JavaScript enabled:
```
<noscript>
	<div id="noscript-warning" style="background-color: #f5f5f5; border: 2px solid #f00; padding: 0px 10px;  max-width: 500px;">
		<h3>JavaScript required</h3>
		<p>Sorry, this file upload tool requires JavaScript to be running. Please check that you have an up-to-date browser and that your settings allow JavaScript to run.</p>
	</div>
</noscript>
```
