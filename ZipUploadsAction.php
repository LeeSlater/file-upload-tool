<?php

date_default_timezone_set('Europe/London');

require_once "xforms2/JaduXFormsUserForms.php";

class Jadu_Custom_XFP_Action_ZipUploadsAction extends Jadu_XForms2_Action_Abstract
{
	const LABEL = 'Zip File Uploads';
	const SCRIPT_FILENAME = 'ZipUploadsTab.php';

	/**
	* ID associated with the tab, this is pulled from the database and populated via the constructor
	* @var integer
	* @access public
	*/
	public $id;

	/**
	* Instance of {@link XFormsForm}
	* @var object
	* @access private
	*/
	protected $form;

	/**
	* Constructor takes object of type {@link XFormsForm} as the only argument
	*/
	function __construct($form, $id)
	{
		parent::__construct($form, $id);
		$this->form = $form;
		$this->id = $id;
	}

	/**
	* Prior to PHP 5.3 it is not possible to do $class::LABEL
	* This method is a workaround until we only support PHP 5.3 or above
	*/
	public function getLabel()
	{
		return self::LABEL;
	}

	/**
	* Returns an array of script filenames associated with the tab.
	* @return array[]string Script filenames
	*/
	public function scripts()
	{
		/*return array(
			self::SCRIPT_FILENAME, 'xforms2/SetBalanceTab.php', 'SetBalanceTab.php',
			'xforms_form_request_access.php?formID=' . intval($this->form->id) . '&resourceID=' . intval($this->id)
		);*/
	}

	/**
	* Relative URL for tab link.
	* e.g. xforms_pdf_forms.php?formID=1
	*
	* @return string Relative URL
	*/
	public function url()
	{
		//return self::SCRIPT_FILENAME . '?formID=' . intval($this->form->id);
	}

	/**
	* Always installed
	*
	* @return true
	*/
	public function installed()
	{
		return true;
	}

	/**
	* Needs a control centre tab
	*
	* @return true
	*/
	public function showTab()
	{
		return false;
	}

	/**
	* Initial Status is not a lockable feature
	*
	* @return boolean
	*/
	public function lockable()
	{
		return true;
	}

		/**
	* Example action does not require any settings
	* @return string
	*/
	public function settings($enabled = false)
	{
		return '';
	}

	/**
	* Example action does not require any settings
	* @param array $settings
	* @return true
	*/
	public function addSettings($settings = array())
	{
		// no settings
	}

	/**
	* Example action does not require any settings
	* @return true
	*/
	public function removeSettings()
	{
		// no settings
	}

	public function execute(Jadu_XForms2_Action_Result $resultSet)
	{
		require_once "/var/www/jadu/public_html/site/custom_scripts/repo/apps/file-uploads/uploader/zip-uploads.php";
		zip_uploaded_files($this->form->id, $this->userForm->id);
	}
}
