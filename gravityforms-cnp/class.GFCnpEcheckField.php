<?php

/**
* with thanks to Travis Smith's excellent tutorial:
* http://wpsmith.net/2011/plugins/how-to-create-a-custom-form-field-in-gravity-forms-with-a-terms-of-service-form-field-example/
*/
class GFCnpEcheckField {

	protected $plugin;
	protected $RecurringMethod;
	protected $first_load;

	/**
	* @param GFEwayPlugin $plugin
	*/
	public function __construct($plugin) {
		$this->plugin = $plugin;
		
		$this->RecurringMethod = array();
		$this->first_load = true;
		// WordPress script hooks -- NB: must happen after Gravity Forms registers scripts
		add_action('wp_enqueue_scripts', array($this, 'registerScripts'), 20);
		add_action('admin_enqueue_scripts', array($this, 'registerScripts'), 20);

		// add Gravity Forms hooks
		add_action('gform_enqueue_scripts', array($this, 'gformEnqueueScripts'), 20, 2);
		add_action('gform_editor_js', array($this, 'gformEditorJS'));
		add_action('gform_field_standard_settings', array($this, 'gformFieldStandardSettings'), 10, 2);
		add_filter('gform_add_field_buttons', array($this, 'gformAddFieldButtons'));
		add_filter('gform_field_type_title', array($this, 'gformFieldTypeTitle'), 10, 2);
		add_filter('gform_field_input', array($this, 'gformFieldInput'), 10, 5);
		add_filter('gform_pre_validation', array($this, 'gformPreValidation'));
		add_filter('gform_field_validation', array($this, 'gformFieldValidation'), 10, 4);
		add_filter('gform_tooltips', array($this, 'gformTooltips'));
		add_filter('gform_pre_submission', array($this, 'gformPreSubmit'));

		if (is_admin()) {
			add_filter('gform_field_css_class', array($this, 'watchFieldType'), 10, 2);
		}
		
	}

	/**
	* register and enqueue required scripts
	* NB: must happen after Gravity Forms registers scripts
	*/
	public function registerScripts() {
		// recurring payments field has datepickers; register required scripts / stylesheets
		if (version_compare(GFCommon::$version, '1.7.6.99999', '<')) {
			// pre-1.7.7 script registrations
			$gfBaseUrl = GFCommon::get_base_url();
			wp_register_script('gforms_ui_datepicker', $gfBaseUrl . '/js/jquery-ui/ui.datepicker.js', array('jquery'), GFCommon::$version, true);
			wp_register_script('gforms_datepicker', $gfBaseUrl . '/js/datepicker.js', array('gforms_ui_datepicker'), GFCommon::$version, true);
			$reqs = array('gforms_datepicker');
		}
		else {
			// post-1.7.7
			$reqs = array('gform_datepicker_init');
		}

		$min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
		wp_register_script('gfcnp_echeck', "{$this->plugin->urlBase}js/recurring$min.js", $reqs, GFCNP_PLUGIN_VERSION, true);

		wp_register_style('gfcnp', $this->plugin->urlBase . 'style.css', false, GFCNP_PLUGIN_VERSION);
	}

	/**
	* enqueue additional scripts if required by form
	* @param array $form
	* @param boolean $ajax
	*/
	public function gformEnqueueScripts($form, $ajax) {
		if ($this->plugin->hasFieldType($form['fields'], GFCNP_FIELD_ECHECK)) {
			// enqueue script for field
			wp_enqueue_script('gfcnp_echeck');

			// enqueue default styling
			wp_enqueue_style('gfcnp');
		}

	}

	/**
	* load custom script for editor form
	*/
	public function gformEditorJS() {
		$version = GFCNP_PLUGIN_VERSION;
		$min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
		echo "<script src=\"{$this->plugin->urlBase}js/admin-echeck$min.js?v=$version\"></script>\n";
	}

	/**
	* filter hook for modifying the field buttons on the forms editor
	* @param array $field_groups array of field groups; each element is an array of button definitions
	* @return array
	*/
	public function gformAddFieldButtons($field_groups) {
		foreach ($field_groups as &$group) {
			if ($group['name'] == 'pricing_fields') {
				$group['fields'][] = array (
					'class' => 'button',
					'value' => 'eCheck',
					'name' => 'EcheckButton',
					'id' => 'EcheckButton',
					'onclick' => "StartAddField('" . GFCNP_FIELD_ECHECK . "');",
				);
				break;
			}
		}
		return $field_groups;
	}

	/**
	* filter hook for modifying the field title (e.g. on custom fields)
	* @param string $title
	* @param string $field_type
	* @return string
	*/
	public function gformFieldTypeTitle($title, $field_type) {
		if ($field_type == GFCNP_FIELD_ECHECK) {
			$title = 'eCheck';
		}

		return $title;
	}

	/**
	* add custom fields to form editor
	* @param integer $position
	* @param integer $form_id
	*/
	public function gformFieldStandardSettings($position, $form_id) {
		// add inputs for labels right after the field label input
		if ($position == 25) {
			$options = $this->plugin->options;
			?>
			<li class="gfcnpecheck_setting field_setting"></li>	
			<?php
		}
	}

	/**
	* add custom tooltips for fields on form editor
	* @param array $tooltips
	* @return array
	*/
	public function gformTooltips($tooltips) {
		$tooltips['gfcnp_initial_setting'] = "<h6>Show Initial Amount</h6>Select this option to show Initial Amount and Initial Date fields.";
		$tooltips['gfcnp_initial_amount_label'] = "<h6>Initial Amount</h6>The label shown for the Initial Amount field.";
		$tooltips['gfcnp_initial_date_label'] = "<h6>Initial Date</h6>The label shown for the Initial Date field.";
		$tooltips['gfcnp_recurring_amount_label'] = "<h6>Recurring Amount</h6>The label shown for the Recurring Amount field.";
		$tooltips['gfcnp_recurring_date_setting'] = "<h6>Show Start/End Dates</h6>Select this option to show Start Date and End Date fields.";
		$tooltips['gfcnp_start_date_label'] = "<h6>Start Date</h6>The label shown for the Start Date field.";
		$tooltips['gfcnp_end_date_label'] = "<h6>End Date</h6>The label shown for the End Date field.";
		$tooltips['gfcnp_interval_type_label'] = "<h6>Interval Type</h6>The label shown for the Interval Type field.";

		return $tooltips;
	}
	/**
	* get input values for recurring payments field
	* @param integer $field_id
	* @return array
	*/
	public static function getPost($field_id) {
		$echeck = rgpost('gfp_' . $field_id);
		
		if (is_array($echeck)) {
			$echeck = array (
				'routing' => $echeck['routing'],
				'check' => $echeck['check'],
				'account' => $echeck['account'],
				'reaccount' => $echeck['reaccount'],
				'account_type' => $echeck['account_type'],
				'name' => $echeck['name'],
				'checktype' => $echeck['checktype'],
				'idtype' => $echeck['idtype'],
			);
		}
		else {
			$echeck = false;
		}

		return $echeck;
	}
	/**
	* grab values and concatenate into a string before submission is accepted
	* @param array $form
	*/
	public function gformPreSubmit($form) {
	
		foreach ($form['fields'] as $field) {
			if ($field['type'] == GFCNP_FIELD_ECHECK && !RGFormsModel::is_field_hidden($form, $field, RGForms::post('gform_field_values'))) {
				$echeck = self::getPost($field['id']);			
				
				$str = "Your amount will be charged from Routing Number :<b>{$echeck['routing']}</b>\nCheck Number : <b>{$echeck['check']}</b>\nAccount Number : <b>{$echeck['account']}</b>\nAccount Type :<b>{$echeck['account_type']}</b>\nName on Account : <b>{$echeck['name']}</b>\nCheck Type : <b>{$echeck['checktype']}</b>\nType of ID : <b>{$echeck['idtype']}</b>";
				$_POST["input_{$field['id']}"] = $str;
				
			}
		}
		
	}

	/**
	* prime the inputs that will be checked by standard validation tests,
	* e.g. so that "required" fields don't fail
	* @param array $form
	* @return array
	*/
	public function gformPreValidation($form) {
		
		foreach($form["fields"] as $field) {
			if (($field['type'] == GFCNP_FIELD_ECHECK) && (isset($_POST['gfp_'.$field['id']]))) {
				$echeck = self::getPost($field['id']);				
				$_POST["input_{$field['id']}"] = serialize($echeck);
				$this->first_load = false;				
			}
		}
		return $form;
	}

	/**
	* validate inputs
	* @param array $validation_result an array with elements is_valid (boolean) and form (array of form elements)
	* @param string $value
	* @param array $form
	* @param array $field
	* @return array
	*/
	public function gformFieldValidation($validation_result, $value, $form, $field) {
		
		if ($field['type'] == GFCNP_FIELD_ECHECK) {
			if (!RGFormsModel::is_field_hidden($form, $field, RGForms::post('gform_field_values')) && (isset($_POST['gfp_'.$field['id']]))) {
				// get the real values
				$value = self::getPost($field['id']);
				
				if (!is_array($value)) {
					$validation_result['is_valid'] = false;
					$validation_result['message'] = __("This field is required.", "gravityforms");
				}
				else {
					$formData2 = new GFCnpFormData($form);
					
					if($formData2->creditcardCount == 0)
					{
						$messages = array();
						if (empty($value['routing'])) {
							$messages[] = "Please enter Routing Number.";
						}
						if (strlen($value['routing']) > 9) {
							$messages[] = "Routing Number should be max. 9 digits only.";
						}
						if (empty($value['check'])) {
							$messages[] = "Please enter Check Number.";
						}
						if (strlen($value['check']) > 10) {
							$messages[] = "Check Number should be max. 10 digits only.";
						}
						if (empty($value['account'])) {
							$messages[] = "Please enter Account Number.";
						}
						if (strlen($value['account']) > 17) {
							$messages[] = "Account Number should be max. 17 digits only.";
						}
						if (empty($value['reaccount'])) {
							$messages[] = "Please enter Repeat Account Number.";
						}
						if (strlen($value['reaccount']) > 17) {
							$messages[] = "Repeat Account Number should be max. 17 digits only.";
						}
						if ($value['account'] != $value['reaccount']) {
							$messages[] = "Account number not match.";
						}
						if(empty($value['account_type'])) {
							$messages[] = "Please select Account Type.";
						}
						if(empty($value['name'])) {
							$messages[] = "Please enter Name on Account.";
						}
						if (strlen($value['name']) > 100) {
							$messages[] = "Name on Account should be max. 100 characters only.";
						}
						if(empty($value['checktype'])) {
							$messages[] = "Please select Check Type.";
						}
						if(empty($value['idtype'])) {
							$messages[] = "Please select Type of ID.";
						}
						if (count($messages) > 0) {
							$validation_result['is_valid'] = false;
							$validation_result['message'] = implode("<br />\n", $messages);
						}
					}
				}
			}
		}
		return $validation_result;
	}


	/**
	* watch the field type so that we can use hooks that don't pass enough information
	* @param string $classes
	* @param array $field
	* @return string
	*/
	public function watchFieldType($classes, $field) {
		// if field type matches, add filters that don't allow testing for field type
		if ($field['type'] == GFCNP_FIELD_ECHECK) 
		{
			//echo GFCNP_FIELD_ECHECK.':Adi';
			add_filter('gform_duplicate_field_link', array($this, 'gformDuplicateFieldLink'));
		}
		return $classes;
	}

	/**
	* filter the field duplication link, we don't want one for this field type
	* @param string $duplicate_field_link
	* @return $duplicate_field_link
	*/
	public function gformDuplicateFieldLink($duplicate_field_link) {
		// remove filter once called, only process current field
		//remove_filter('gform_duplicate_field_link', array($this, __FUNCTION__));
		add_filter('gform_duplicate_field_link', array($this, __FUNCTION__));
		// erase duplicate field link for this field
		return '';
	}

	/**
	* filter hook for modifying a field's input tag (e.g. on custom fields)
	* @param string $input the input tag before modification
	* @param array $field
	* @param string $value
	* @param integer $lead_id
	* @param integer $form_id
	* @return string
	*/
	public function gformFieldInput($input, $field, $value, $lead_id, $form_id) {
		//print_r($field);
		if ($field['type'] == GFCNP_FIELD_ECHECK) {
			// pick up the real value
			$value = rgpost('gfcnp_' . $field['id']);
			
			$echeck = $_POST['gfp_' . $field['id']];
			$isadmin = ( IS_ADMIN ) ? TRUE : FALSE;
			//echo '<pre>';
			//print_r($echeck);
			//die();
			//print_r($field);
			//echo $field['gfcnp_maxrecurrings_Installment_'.$field['formId']];
			//print_r(rgpost('gfcnp_maxrecurrings_Installment_' . $field['formId']));
			//die();
			$disabled_text = (IS_ADMIN && RG_CURRENT_VIEW != "entry") ? "disabled='disabled' " : "";
			$css = isset($field['cssClass']) ? esc_attr($field['cssClass']) : '';
			
			$input = "<div class='ginput_complex ginput_container gfcnp_echeck_complex $css' id='input_{$field['id']}'>";
			$isrecurring = empty($isrecurr) ? '' : ' checked';
			$disabled_text = (IS_ADMIN && RG_CURRENT_VIEW != "entry") ? " disabled='disabled'" : "";
			$input .= "Routing Number <span class='gfield_required'>*</span>: <input type='text' name='gfp_{$field['id']}[routing]' id='gfp_{$field['id']}'  value='{$echeck['routing']}' class='echeck_routing'$isrecurring$disabled_text><br>";
			
			$input .= "Check Number <span class='gfield_required'>*</span>: <input type='text' name='gfp_{$field['id']}[check]' id='gfp_{$field['id']}_check'  value='{$echeck['check']}' class='echeck_check'$isrecurring$disabled_text><br>";
			
			$input .= "Account Number <span class='gfield_required'>*</span>: <input type='text' name='gfp_{$field['id']}[account]' id='gfp_{$field['id']}_account'  value='{$echeck['account']}' class='echeck_account'$isrecurring$disabled_text><br>";
			
			$input .= "Repeat Account Number <span class='gfield_required'>*</span>: <input type='text' name='gfp_{$field['id']}[reaccount]' id='gfp_{$field['id']}_account'  value='{$echeck['reaccount']}' class='echeck_account'$isrecurring$disabled_text><br>";
			
			$SavingsAccount = '';
			$CheckingAccount = '';
			if($echeck['account_type'] == 'SavingsAccount')
			$SavingsAccount = 'selected';
			if($echeck['account_type'] == 'CheckingAccount')
			$CheckingAccount = 'selected';
			$input .= "Account Type : <select name='gfp_{$field['id']}[account_type]' id='gfp_{$field['id']}_account_type' class='echeck_account_type'$isrecurring$disabled_text><option value='SavingsAccount' $SavingsAccount>SavingsAccount</option><option value='CheckingAccount' $CheckingAccount>CheckingAccount</option></select><br>";
			
			$input .= "Name on Account <span class='gfield_required'>*</span>: <input type='text' name='gfp_{$field['id']}[name]' id='gfp_{$field['id']}_name' value='{$echeck['name']}' class='echeck_name'$isrecurring$disabled_text><br>";
			
			$Company = '';
			$Personal = '';
			if($echeck['checktype'] == 'Company')
			$Company = 'selected';
			if($echeck['checktype'] == 'Personal')
			$Personal = 'selected';
			$input .= "Check Type : <select name='gfp_{$field['id']}[checktype]' id='gfp_{$field['id']}_checktype' class='echeck_checktype'$isrecurring$disabled_text><option value='Company' $Company>Company</option><option value='Personal' $Personal>Personal</option></select><br>";
			
			$Driver = '';
			$Military = '';
			$State = '';
			if($echeck['checktype'] == 'Driver')
			$Driver = 'selected';
			if($echeck['checktype'] == 'Military')
			$Military = 'selected';
			if($echeck['checktype'] == 'State')
			$State = 'selected';
			$input .= "Type of ID : <select name='gfp_{$field['id']}[idtype]' id='gfp_{$field['id']}_idtype' class='echeck_idtype'$isrecurring$disabled_text><option value='Driver' $Driver>Driver</option><option value='Military' $Military>Military</option><option value='State' $State>State</option></select><br>";
			$id = $field["id"];
			$field_id = IS_ADMIN || $form_id == 0 ? "input_{$id}" : "input_{$form_id}_{$id}";	
			$input .= "<input type='hidden' name='input_{$id}' id='$field_id' />";
			$input .= "</div>";
		}

		return $input;
	}
}
