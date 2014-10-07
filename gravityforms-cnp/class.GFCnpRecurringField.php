<?php

/**
* with thanks to Travis Smith's excellent tutorial:
* http://wpsmith.net/2011/plugins/how-to-create-a-custom-form-field-in-gravity-forms-with-a-terms-of-service-form-field-example/
*/
class GFCnpRecurringField {

	protected $plugin;
	protected $RecurringMethod;
	protected $first_load;

	protected static $defaults = array (
		'gfcnp_initial_amount_label' => 'Initial Amount',
		'gfcnp_recurring_amount_label' => 'Recurring Amount',
		'gfcnp_initial_date_label' => 'Initial Date',
		'gfcnp_start_date_label' => 'Start Date',
		'gfcnp_end_date_label' => 'End Date',
		'gfcnp_interval_type_label' => 'Interval Type',
	);

	/**
	* @param GFEwayPlugin $plugin
	*/
	public function __construct($plugin) {
		error_reporting(0);
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
		wp_register_script('gfcnp_recurring', "{$this->plugin->urlBase}js/recurring$min.js", $reqs, GFCNP_PLUGIN_VERSION, true);

		wp_register_style('gfcnp', $this->plugin->urlBase . 'style.css', false, GFCNP_PLUGIN_VERSION);
	}

	/**
	* enqueue additional scripts if required by form
	* @param array $form
	* @param boolean $ajax
	*/
	public function gformEnqueueScripts($form, $ajax) {
		if ($this->plugin->hasFieldType($form['fields'], GFCNP_FIELD_RECURRING)) {
			// enqueue script for field
			wp_enqueue_script('gfcnp_recurring');

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
		echo "<script src=\"{$this->plugin->urlBase}js/admin-recurring$min.js?v=$version\"></script>\n";
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
					'value' => 'Recurring',
					'name' => 'RecurringButton',
					'id' => 'RecurringButton',
					'onclick' => "StartAddField('" . GFCNP_FIELD_RECURRING . "');",
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
		if ($field_type == GFCNP_FIELD_RECURRING) {
			$title = 'Recurring Payments';
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
			//var_dump($this->first_load);
			?>
			<li class="gfcnprecurring_setting field_setting">
				<div id="RecurringMethod">
						<label for="gfcnp_recurring_RecurringMethod_label">
						Recurring Methods
						<?php gform_tooltip("gfeway_recurring_RecurringMethod_label") ?>
						<?php gform_tooltip("gfeway_recurring_RecurringMethod_label_html") ?>
					</label>
						<input type="checkbox" id="Subscription" value="Subscription" onclick="GFCnpRecurring.ToggleSubscriptionSetting(this)">&nbsp;Subscription<br>
						<div id="maxrecurrings_Subscription_label" style="display:none;">
							<input type="text" id="gfcnp_maxrecurrings_Subscription" onchange="GFCnpRecurring.FieldSet(this)">Subscription Max. Recurrings Allowed<br>
						</div>	
						
						<input type="checkbox" id="Installment" value="Installment" onclick="GFCnpRecurring.ToggleInstallmentSetting(this)">&nbsp;Installment<br>
						<div id="maxrecurrings_Installment_label" style="display:none;">
							<input type="text" id="gfcnp_maxrecurrings_Installment" onchange="GFCnpRecurring.FieldSet(this)">Installment Max. Recurrings Allowed<br>
						</div>
					</div>
					
				<label for="gfcnp_recurring_amount_label">
						Periods
						<?php gform_tooltip("gfeway_recurring_amount_label") ?>
						<?php gform_tooltip("gfeway_recurring_amount_label_html") ?>
					</label>
					
					<input type="checkbox" id="gfcnp_Week_setting" value="Week" onclick="GFCnpRecurring.FieldSet2(this)" <?php echo $Week;?>>&nbsp;Week<br>
					<input type="checkbox" id="gfcnp_2_Weeks_setting" value="2 Weeks" onclick="GFCnpRecurring.FieldSet2(this)">&nbsp;2 Weeks<br>
					<input type="checkbox" id="gfcnp_Month_setting" value="Month" onclick="GFCnpRecurring.FieldSet2(this)">&nbsp;Month<br>
					<input type="checkbox" id="gfcnp_2_Months_setting" value="2 Months" onclick="GFCnpRecurring.FieldSet2(this)">&nbsp;2 Months<br>
					<input type="checkbox" id="gfcnp_Quarter_setting" value="Quarter" onclick="GFCnpRecurring.FieldSet2(this)">&nbsp;Quarter<br>
					<input type="checkbox" id="gfcnp_6_Months_setting" value="6 Months" onclick="GFCnpRecurring.FieldSet2(this)">&nbsp;6 Months<br>
					<input type="checkbox" id="gfcnp_Year_setting" value="Year" onclick="GFCnpRecurring.FieldSet2(this)">&nbsp;Year<br><br>
					
					
					
					<div id="indefinite_div">
					<input type="checkbox" id="indefinite" value="indefinite" onclick="GFCnpRecurring.FieldSet2(this)">&nbsp;Allow indefinite recurring<br>
					</div>					
			</li>			
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
	* grab values and concatenate into a string before submission is accepted
	* @param array $form
	*/
	public function gformPreSubmit($form) {
		
		foreach ($form['fields'] as $field) {
			if ($field['type'] == GFCNP_FIELD_RECURRING && !RGFormsModel::is_field_hidden($form, $field, RGForms::post('gform_field_values'))) {
				$recurring = self::getPost($field['id']);
				//print_r($recurring);
				//die();
				/*
				$_POST["input_{$field['id']}"] = '$' . number_format($recurring['amountRecur'], 2)
					. " {$recurring['intervalTypeDesc']} from {$recurring['dateStart']->format('d M Y')}";
					*/
				if($recurring['isRecurring'] == 'yes') {
				if($recurring['Installments'])
				$installments = $recurring['Installments'];
				else
				$installments = 999;
				if($recurring['RecurringMethod'] == 'Installment')
				$str = "Your card will be charged every {$recurring['Periodicity']} for {$installments} times (Installment)";
				else
				$str = "Your card will be charged every {$recurring['Periodicity']} for {$installments} times (Subscription)";
				$_POST["input_{$field['id']}"] = $str;
				} else {
					//$_POST["input_{$field['id']}"] = 'Simple Payment';
				}
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
			if ($field['type'] == GFCNP_FIELD_RECURRING && !RGFormsModel::is_field_hidden($form, $field, RGForms::post('gform_field_values')) && isset($_POST['gfp_'.$field['id']]) && $_POST['gfp_'.$field['id']] == 'on') {
				$recurring = self::getPost($field['id']);
				$_POST["input_{$field['id']}"] = serialize($recurring);
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
		
		if ($field['type'] == GFCNP_FIELD_RECURRING) {
			if (!RGFormsModel::is_field_hidden($form, $field, RGForms::post('gform_field_values')) && (isset($_POST['gfp_'.$field['id']]) && $_POST['gfp_'.$field['id']] == 'on')) {
				// get the real values
				$value = self::getPost($field['id']);
				//print_r($value);
				//die();
				if (!is_array($value)) {
					$validation_result['is_valid'] = false;
					$validation_result['message'] = __("This field is required.", "gravityforms");
				}

				else {
					$messages = array();

					if ($value['Installments'] === false || $value['Installments'] < 2) {
						$messages[] = "Please enter a valid value for payments. The value between 2 to 999";
					}
					
					//$options = $this->plugin->options;
					$options = $field;

					if (empty($value['Periodicity'])) {
						$messages[] = "Please select Periodicity.";
					}

					if (empty($value['RecurringMethod'])) {
						$messages[] = "Please select recurring method.";
					}
					
					if($value['Installments'] == 1) {
						$messages[] = "Please enter value greater than 2.";
					}
					if ($value['RecurringMethod'] == 'Subscription') {
						if(!empty($options['gfcnp_maxrecurrings_Subscription'])) {
							if($value['Installments'] > $options['gfcnp_maxrecurrings_Subscription'])
							$messages[] = "Please enter value between 2 to ".$options['gfcnp_maxrecurrings_Subscription'].".";	
						} else {
						//$messages[] = "Please enter value between 2 to 999.";
						}
					} else {
						if(!empty($options['gfcnp_maxrecurrings_Installment'])) {
							if($value['Installments'] > $options['gfcnp_maxrecurrings_Installment'])
							$messages[] = "Please enter value between 2 to ".$options['gfcnp_maxrecurrings_Installment'].".";	
						} else {
						//$messages[] = "Please enter value between 2 to 999.";
						}
					}
					
					if (count($messages) > 0) {
						$validation_result['is_valid'] = false;
						$validation_result['message'] = implode("<br />\n", $messages);
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
		if ($field['type'] == GFCNP_FIELD_RECURRING) 
		{
			//echo GFCNP_FIELD_RECURRING.':Adi';
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
		if ($field['type'] == GFCNP_FIELD_RECURRING) {
			// pick up the real value
			$value = rgpost('gfcnp_' . $field['id']);
			$isrecurr = $_POST['gfp_' . $field['id']];
			$isadmin = ( IS_ADMIN ) ? TRUE : FALSE;
			//echo '<pre>';
			//print_r($value);
			//print_r($field);
			//echo $field['gfcnp_maxrecurrings_Installment_'.$field['formId']];
			//print_r(rgpost('gfcnp_maxrecurrings_Installment_' . $field['formId']));
			//die();
			$disabled_text = (IS_ADMIN && RG_CURRENT_VIEW != "entry") ? "disabled='disabled' " : "";
			$css = isset($field['cssClass']) ? esc_attr($field['cssClass']) : '';
			
			$recurring_label = empty($value[3]) ? '' : $value[3];
			$Period = empty($value[4]) ? '' : $value[4];
			$gfcnp_recurring_maxrecurrings_Installment = $field['gfcnp_maxrecurrings_Installment'];
			$gfcnp_recurring_maxrecurrings_Subscription = $field['gfcnp_maxrecurrings_Subscription'];

			$input = "<div class='ginput_complex ginput_container gfcnp_recurring_complex $css' id='input_{$field['id']}'>";
			$isrecurring = empty($isrecurr) ? '' : ' checked';
			$disabled_text = (IS_ADMIN && RG_CURRENT_VIEW != "entry") ? " disabled='disabled'" : "";
			$input .= "<input type='checkbox' name='gfp_{$field['id']}' id='gfp_{$field['id']}' class='recurring_checkbox'$isrecurring$disabled_text>&nbsp;&nbsp;Is this a recurring payment <br>";
			
			// Recurring Method
			$sub_field = array (
				'type' => 'Recurring Method',
				'id' => $field['id'],
				'sub_id' => '5',
				'label' => 'Recurring Method',
				'value' => $Period,
				'label_class' => 'gfcnp_Installment_label',
			);
			$Periods = array();
			if(isset($field['Installment']) && $field['Installment'] == 1)
			$Periods['Installment'] = 'Installment';
			$input .= $this->fieldCheckbox($sub_field, $Periods, $lead_id, $form_id, 'Recurring Method', $isadmin);
			
			$sub_field = array (
				'type' => 'gfcnp_recurring_maxrecurrings_Installment',
				'id' => $field['id'],
				'sub_id' => '3',
				'label' => 'Max. Recurrings Allowed',
				'isRequired' => true,
				'size' => 'small',
				'label_class' => 'gfcnp_recurring_Installment_label',
			);
			$input .= $this->inputText($sub_field, $gfcnp_recurring_maxrecurrings_Installment, $lead_id, $form_id, 'style="display:none;"', $isadmin);
			
			$sub_field = array (
				'type' => 'Recurring Method',
				'id' => $field['id'],
				'sub_id' => '5',
				'label' => 'Recurring Method',
				'value' => $Period,
				'label_class' => 'gfcnp_Subscription_label',
			);
			//$Periods = array('Subscription' => 'Subscription');
			$Periods = array();
			if(isset($field['Subscription']) && $field['Subscription'] == 1)
			$Periods['Subscription'] = 'Subscription';
			
			//print_r($Periods);
			$input .= $this->fieldCheckbox($sub_field, $Periods, $lead_id, $form_id, '', $isadmin);
			
			$sub_field = array (
				'type' => 'gfcnp_recurring_maxrecurrings_Subscription',
				'id' => $field['id'],
				'sub_id' => '3',
				'label' => 'Max. Recurrings Allowed',
				'isRequired' => true,
				'size' => 'small',
				'label_class' => 'gfcnp_recurring_Subscription_label',
			);
			$input .= $this->inputText($sub_field, $gfcnp_recurring_maxrecurrings_Subscription, $lead_id, $form_id, 'style="display:none;"', $isadmin);
			
			if(count($this->RecurringMethod) > 1)
			{
				$id = $field['id'];
				$sid = 0;
				$input  .= "<div class='ginput_container RecurringMethod'>Recurring Method<ul class='gfield_checkbox'>";
				$input .= "<select name='gfcnp_{$id}_RecurringMethod' class='$v' id='$field_id' >";
				foreach($this->RecurringMethod as $key => $val) { 
				$val_key = key($val);
				$val_value = key($val[key($val)]);
				$input .= "<option value='$val_key|$val_value'>$val_value</option>";
				$sid++;
				}
				$input .= "</select>";
				$input .= "</ul></div>";
			}
			else if(count($this->RecurringMethod) == 0 && !$isadmin)
			{
				$sub_field = array (
				'type' => 'Recurring Method',
				'id' => $field['id'],
				'sub_id' => '5',
				'label' => 'Recurring Method',
				'value' => $Period,
				'label_class' => 'gfcnp_Installment_label',
				);
				$Periods = array();				
				$Periods['Installment'] = 'Installment';
				$input .= $this->fieldCheckbox($sub_field, $Periods, $lead_id, $form_id, 'Recurring Method', $isadmin);
				
				$sub_field = array (
				'type' => 'Recurring Method',
				'id' => $field['id'],
				'sub_id' => '5',
				'label' => 'Recurring Method',
				'value' => $Period,
				'label_class' => 'gfcnp_Subscription_label',
				);				
				$Periods = array();
				$Periods['Subscription'] = 'Subscription';
				$input .= $this->fieldCheckbox($sub_field, $Periods, $lead_id, $form_id, '', $isadmin);
				
				$id = $field['id'];
				$sid = 0;
				$input  .= "<div class='ginput_container RecurringMethod'>Recurring Method<ul class='gfield_checkbox'>";
				$input .= "<select name='gfcnp_{$id}_RecurringMethod' class='$v' id='$field_id' >";
				foreach($this->RecurringMethod as $key => $val) { 
				$val_key = key($val);
				$val_value = key($val[key($val)]);
				$input .= "<option value='$val_key|$val_value'>$val_value</option>";
				$sid++;
				}
				$input .= "</select>";
				$input .= "</ul></div>";
			}
			else
			{
				$id = $field['id'];
				$name = key($this->RecurringMethod[0]);
				$value = key($this->RecurringMethod[0][$name]);
				//print_r($this->RecurringMethod);
				$input .= "<input name='gfcnp_{$id}_RecurringMethod' id='$name' type='hidden' value='$name|$value' />";	
			}
			
			// Periods
			$sub_field = array (
				'type' => 'Periods',
				'id' => $field['id'],
				'sub_id' => '4',
				'label' => 'Periods',
				'value' => $Period,
				'label_class' => 'gfcnp_Periods_label',
			);
			$Periods = array('Week' => 'Week', '2_Weeks' => '2 Weeks', 'Month' => 'Month', '2_Months' => '2 Months', 'Quarter' => 'Quarter', '6_Months' => '6 Months', 'Year' => 'Year');
			
			$selected_Periods = array();
			if(isset($field['gfcnp_Week_setting']) && $field['gfcnp_Week_setting'] == 1)
			$selected_Periods['Week'] = 'Week';
			if(isset($field['gfcnp_2_Weeks_setting']) && $field['gfcnp_2_Weeks_setting'] == 1)
			$selected_Periods['2_Weeks'] = '2 Weeks';
			if(isset($field['gfcnp_Month_setting']) && $field['gfcnp_Month_setting'] == 1)
			$selected_Periods['Month'] = 'Month';
			if(isset($field['gfcnp_2_Months_setting']) && $field['gfcnp_2_Months_setting'] == 1)
			$selected_Periods['2_Months'] = '2 Months';
			if(isset($field['gfcnp_Quarter_setting']) && $field['gfcnp_Quarter_setting'] == 1)
			$selected_Periods['Quarter'] = 'Quarter';
			if(isset($field['gfcnp_6_Months_setting']) && $field['gfcnp_6_Months_setting'] == 1)
			$selected_Periods['6_Months'] = '6 Months';
			if(isset($field['gfcnp_Year_setting']) && $field['gfcnp_Year_setting'] == 1)
			$selected_Periods['Year'] = 'Year';
			//print_r($selected_Periods);
			$input .= $this->fieldCheckbox($sub_field, $selected_Periods, $lead_id, $form_id, 'Periods', $isadmin);
			
			if($field['indefinite'] == 1) {
			$gfpindefinite = empty($_POST['gfpindefinite_' . $field['id']]) ? '' : ' checked';
			$input .= "<span><input type='checkbox' class='indefinite' name='gfpindefinite_{$field['id']}' id='gfpindefinite_{$field['id']}'$disabled_text$gfpindefinite>&nbsp;&nbsp;Indefinite Recurring</span><br>";
			$input .= "<script type='text/javascript'>
				
				jQuery('.indefinite').click(function(){
					if(jQuery('.indefinite').is(':checked')) {
					jQuery('.gfcnp_recurring_label').val('');
						jQuery('.gfcnp_recurring_label').hide();
						jQuery('.gfcnp_recurring_label').prop('readonly', true);						
					}
					else {						
						jQuery('.gfcnp_recurring_label').show();
						jQuery('.gfcnp_recurring_label').prop('readonly', false);						
						}
				});
			</script>";
			}
			//print_r(get_option(GFCNP_PLUGIN_OPTIONS));
			// recurring amount
			$sub_field = array (
				'type' => 'donation',
				'id' => $field['id'],
				'sub_id' => '3',
				'label' => '# of Installments',
				'isRequired' => true,
				'size' => 'medium',
				'label_class' => 'gfcnp_recurring_label',
			);
			
			$instal = $_POST['gfcnp_'.$field['id']];
			$instal = $instal[3];
			$installments = empty($instal) ? '' : $instal;
			$input .= $this->inputText($sub_field, $installments, $lead_id, $form_id, '', $isadmin);
			
			

			
			// concatenated value added to database
			$sub_field = array (
				'type' => 'hidden',
				'id' => $field['id'],
				'isRequired' => true,
			);
			$input .= $this->fieldConcatenated($sub_field, $interval_type, $lead_id, $form_id);

			$input .= "</div>";
		}

		return $input;
	}

	

	/**
	* get HTML for input and label for donation (amount) field
	* @param array $field
	* @param string $value
	* @param integer $lead_id
	* @param integer $form_id
	* @return string
	*/
	protected function inputText($field, $value="", $lead_id=0, $form_id=0, $style='', $isadmin=TRUE) {
		$id = $field["id"];
		$sub_id = $field["sub_id"];
		$field_id = IS_ADMIN || $form_id == 0 ? "gfcnp_{$id}_{$sub_id}" : "gfcnp_{$form_id}_{$id}_{$sub_id}";
		$form_id = IS_ADMIN && empty($form_id) ? rgget("id") : $form_id;

		$size = rgar($field, "size");
		$disabled_text = (IS_ADMIN && RG_CURRENT_VIEW != "entry") ? "disabled='disabled'" : "";
		//$isadmin = ( IS_ADMIN ) ? TRUE : FALSE;
		$class_suffix = RG_CURRENT_VIEW == "entry" ? "_admin" : "";
		$class = $size . $class_suffix;

		$tabindex = GFCommon::get_tabindex();
		//~ $logic_event = GFCommon::get_logic_event($field, "keyup");

		$spanClass = '';
		if (!empty($field['hidden'])) {
			$spanClass = 'gf_hidden';
		}

		$value = esc_attr($value);		
		$class = esc_attr($class);	

		$label = htmlspecialchars($field['label']);

		if($isadmin) {
		$input  = "<span class='gfcnp_recurring_left $spanClass' $style>";
		$input .= "<label class='{$field['label_class']}' for='$field_id' id='{$field_id}_label'>$label</label>";	
		
		$input .= "<input name='gfcnp_{$id}[{$sub_id}]' id='$field_id' type='text' value='$value' class='{$field['label_class']} ginput_amount $class' $tabindex $disabled_text />";
			
		$input .= "</span>";
		} else {
			if(in_array($field['label_class'], array('gfcnp_recurring_label'))) {
			$input  = "<span class='gfcnp_recurring_left $spanClass' $style>";
			$input .= "<label class='{$field['label_class']}' for='$field_id' id='{$field_id}_label'>$label</label>";
			$input .= "<input name='gfcnp_{$id}[{$sub_id}]' id='$field_id' maxlength='3' type='text' value='$value' class='{$field['label_class']} ginput_quantity $class' $tabindex $disabled_text />";		
			$input .= "</span>";
			}
		}

		return $input;
	}
	
	protected function fieldCheckbox($field, $value="", $lead_id=0, $form_id=0, $title='', $isadmin=TRUE) {
		$id = $field["id"];
		$sub_id = $field["sub_id"];
		$field_id = IS_ADMIN || $form_id == 0 ? "gfcnp_{$id}_{$sub_id}" : "gfcnp_{$form_id}_{$id}_{$sub_id}";
		$form_id = IS_ADMIN && empty($form_id) ? rgget("id") : $form_id;

		$size = rgar($field, "size");
		$disabled_text = (IS_ADMIN && RG_CURRENT_VIEW != "entry") ? "disabled='disabled'" : "";
		$class_suffix = RG_CURRENT_VIEW == "entry" ? "_admin" : "";
		$class = $size . $class_suffix;

		$tabindex = GFCommon::get_tabindex();
		//~ $logic_event = GFCommon::get_logic_event($field, "keyup");

		$spanClass = '';
		$inputClass = array($size . $class_suffix);
		if (!empty($field['hidden'])) {
			$spanClass = 'gf_hidden';
		}
		if (empty($field['hidden'])) {
			$inputClass[] = 'datepicker';
		}
		else {
			$spanClass[] = 'gf_hidden';
		}
		//$value = esc_attr($value);
		$class = esc_attr($class);
		$inputClass = esc_attr(implode(' ', $inputClass));
		$label = htmlspecialchars($field['label']);
		$sid = $sub_id;
		
		if($isadmin) {
		$input  = "<div class='ginput_container'>$title<ul class='gfield_checkbox'>";
		foreach($value as $v) { 
		$input .= "<li><label>$v</label><input name='gfcnp_{$id}[{$sid}]' class='$v' id='$field_id' type='checkbox' checked value='gfcnp_{$id}[{$sid}]|$v'  $tabindex $disabled_text/></li><br>";
		$sid++;
		}
		$input .= "</ul></div>";
		} else {
			
				if(in_array($field['label_class'], array('gfcnp_Periods_label'))) 
				{
					//$Periods = unserialize($this->plugin->options['Periods']);
					$Periods = $value;
					//print_r($Periods);
					//die('ffffff');
					if(count($Periods) == 0 && !$isadmin)
					{
						$Periods = array('Week' => 'Week', '2_Weeks' => '2 Weeks', 'Month' => 'Month', '2_Months' => '2 Months', 'Quarter' => 'Quarter', '6_Months' => '6 Months', 'Year' => 'Year');
						$selected = $_POST['gfcnp_'.$id];
						$input  = "<div class='ginput_container'>$title<ul class='gfield_checkbox'><select name='gfcnp_{$id}[{$sid}]' class='".$field['label_class']."' id='$field_id' >";
						foreach($Periods as $v) { 
						if($selected[4] == 'gfcnp_'.$id.'['.$sid.']|'.$v) {
						$input .= "<option value='gfcnp_{$id}[{$sid}]|$v' selected>$v</option>";
						} else {
						$input .= "<option value='gfcnp_{$id}[{$sid}]|$v'>$v</option>";
						}
						$sid++;
						}
						$input .= "</select></ul></div>";
					}
					else if(count($Periods) > 1) 
					{
						$selected = $_POST['gfcnp_'.$id];
						$input  = "<div class='ginput_container'>$title<ul class='gfield_checkbox'><select name='gfcnp_{$id}[{$sid}]' class='".$field['label_class']."' id='$field_id' >";
						foreach($Periods as $v) { 
						if($selected[4] == 'gfcnp_'.$id.'['.$sid.']|'.$v) {
						$input .= "<option value='gfcnp_{$id}[{$sid}]|$v' selected>$v</option>";
						} else {
						$input .= "<option value='gfcnp_{$id}[{$sid}]|$v'>$v</option>";
						}
						$sid++;
						}
						$input .= "</select></ul></div>";
					}
					else
					{
						$keys = array_values($Periods);
						if($_POST['gfcnp_'.$id.'_Periodicity'])
						$selected = $_POST['gfcnp_'.$id.'_Periodicity'];
						else
						$selected = $keys[0];
						//print_r($Periods);
						//print_r($keys);
						//die('ffff');
						$input .= "<input type='hidden' name='gfcnp_{$id}[{$sid}]' value='".$selected."'>";
					}
				}
				
				if(in_array($field['label_class'], array('gfcnp_Installment_label', 'gfcnp_Subscription_label'))) 
				{
					//$options = unserialize($this->plugin->options['RecurringMethods']);
					$options = $value;
					if(in_array(key($value), $options)) {
					$this->RecurringMethod[]['gfcnp_'.$id.'['.$sid.']'] = $value;
					}
					//print_r($this->RecurringMethod);
				}
		}

		return $input;
	}
	
	/**
	* get HTML for input and label for Interval Type field
	* @param array $field
	* @param string $value
	* @param integer $lead_id
	* @param integer $form_id
	* @return string
	*/
	protected function fieldIntervalType($field, $value="", $lead_id=0, $form_id=0) {
		$id = $field["id"];
		$sub_id = $field["sub_id"];
		$field_id = IS_ADMIN || $form_id == 0 ? "gfcnp_{$id}_{$sub_id}" : "gfcnp_{$form_id}_{$id}_{$sub_id}";
		$form_id = IS_ADMIN && empty($form_id) ? rgget("id") : $form_id;

		$size = rgar($field, "size");
		$disabled_text = (IS_ADMIN && RG_CURRENT_VIEW != "entry") ? "disabled='disabled'" : "";
		$class_suffix = RG_CURRENT_VIEW == "entry" ? "_admin" : "";
		$class = $size . $class_suffix;

		$tabindex = GFCommon::get_tabindex();

		$spanClass = '';
		if (!empty($field['hidden'])) {
			$spanClass = 'gf_hidden';
		}

		$class = esc_attr($class);

		$label = htmlspecialchars($field['label']);

		$periods = apply_filters('gfcnp_recurring_periods', array('weekly', 'fortnightly', 'monthly', 'quarterly', 'yearly'), $form_id, $field);
		if (count($periods) == 1) {
			// build a hidden field and label
			$input  = "<span class='gfcnp_recurring_left $spanClass'>";
			$input .= "<input type='hidden' name='gfcnp_{$id}[{$sub_id}]' value='{$periods[0]}' />";
			$input .= "<label class='{$field['label_class']}' for='$field_id' id='{$field_id}_label'>$label: {$periods[0]}</label>";
			$input .= "</span>";
		}
		else {
			// build a drop-down list
			$opts = '';
			foreach ($periods as $period) {
				$opts .= "<option value='$period'";
				if ($period == $value)
					$opts .= " selected='selected'";
				$opts .= ">$period</option>";
			}

			$input  = "<span class='gfcnp_recurring_left $spanClass'>";
			$input .= "<select size='1' name='gfcnp_{$id}[{$sub_id}]' id='$field_id' $tabindex class='gfield_select $class' $disabled_text>$opts</select>";
			$input .= "<label class='{$field['label_class']}' for='$field_id' id='{$field_id}_label'>$label</label>";
			$input .= "</span>";
		}

		return $input;
	}

	/**
	* get HTML for hidden input with concatenated value for complex field
	* @param array $field
	* @param string $value
	* @param integer $lead_id
	* @param integer $form_id
	* @return string
	*/
	protected function fieldConcatenated($field, $value="", $lead_id=0, $form_id=0) {
		$id = $field["id"];
		$field_id = IS_ADMIN || $form_id == 0 ? "input_{$id}" : "input_{$form_id}_{$id}";
		$form_id = IS_ADMIN && empty($form_id) ? rgget("id") : $form_id;

		$input = "<input type='hidden' name='input_{$id}' id='$field_id' />";
		
		if(IS_ADMIN) {
		$input .= "<script type='text/javascript'>
			/*jQuery(document).ready(function(){
				jQuery('#RecurringButton').hide();
			});		
			*/
			jQuery('.Installment').click(function(){
				if(jQuery('.Installment').is(':checked'))
				jQuery('.gfcnp_recurring_Installment_label').closest('span').show();
				else
				jQuery('.gfcnp_recurring_Installment_label').closest('span').hide();
			});
			
			jQuery('.Subscription').click(function(){
				if(jQuery('.Subscription').is(':checked'))
				jQuery('.gfcnp_recurring_Subscription_label').closest('span').show();
				else
				jQuery('.gfcnp_recurring_Subscription_label').closest('span').hide();
			});
		
		</script>";
		} else {
			$input .= "<script type='text/javascript'>
				jQuery(document).ready(function(){
					
					togglerecurring();
					jQuery('.recurring_checkbox').click(function(){
						togglerecurring();
					});
					
					function togglerecurring()
					{
						if(jQuery('.recurring_checkbox').is(':checked')) {
							jQuery('.indefinite').closest('span').show();
							jQuery('.gfcnp_recurring_label').closest('span').show();
							jQuery('.gfcnp_Periods_label').closest('div').show();
							jQuery('.RecurringMethod').show();
						} else {
							jQuery('.indefinite').closest('span').hide();
							jQuery('.gfcnp_recurring_label').closest('span').hide();
							jQuery('.gfcnp_Periods_label').closest('div').hide();
							jQuery('.RecurringMethod').hide();
						}
					}
				});			
			</script>";
		}

		return $input;
	}

	/**
	* safe checkdate function that verifies each component as numeric and not empty, before calling PHP's function
	* @param string $month
	* @param string $day
	* @param string $year
	* @return boolean
	*/
	protected static function checkdate($month, $day, $year) {
		if (empty($month) || !is_numeric($month) || empty($day) || !is_numeric($day) || empty($year) || !is_numeric($year) || strlen($year) != 4)
			return false;

		return checkdate($month, $day, $year);
	}

	/**
	* get input values for recurring payments field
	* @param integer $field_id
	* @return array
	*/
	public static function getPost($field_id) {
		$recurring = rgpost('gfcnp_' . $field_id);
		//echo '<pre>';
		//print_r($_POST);
		//die();
		if (is_array($recurring)) {
			$Periodicity = explode('|', $recurring[4]);
			if(count($Periodicity) > 1)
			$Periodicity = $Periodicity[1];
			else
			$Periodicity = $recurring[4];
			
			list($f, $RecurringMethod) = explode('|', $_POST['gfcnp_'.$field_id.'_RecurringMethod']);
			/*
			if(count($RecurringMethod) > 1)
			$RecurringMethod = $RecurringMethod[1];
			else
			$RecurringMethod = $recurring[5];
			*/
			
			$indefinite = 'no';
			if(isset($_POST['"gfpindefinite_'.$field_id]) && $_POST['"gfpindefinite_'.$field_id] == 'on') {
			$Installments = 999;
			$indefinite = 'yes';
			}
			elseif($recurring[3])			
			$Installments = GFCommon::to_number($recurring[3]);
			else
			$Installments = 999;
			
			if(isset($_POST['gfp_'.$field_id]) && $_POST['gfp_'.$field_id] == 'on')
			$isRecurring = 'yes';
			else
			$isRecurring = 'no';
			$recurring = array (
				'Installments' => $Installments,
				'Periodicity' => $Periodicity,
				'RecurringMethod' => $RecurringMethod,
				'isRecurring' => $isRecurring,
				'indefinite' => $indefinite,
			);
		}
		else {
			$recurring = false;
		}

		return $recurring;
	}

	/**
	* no date_create_from_format before PHP 5.3, so roll-your-own
	* @param string $value date value in dd/mm/yyyy format
	* @return DateTime
	*/
	protected static function parseDate($value) {
		if (preg_match('#(\d{1,2})/(\d{1,2})/(\d{4})#', $value, $matches)) {
			$date = date_create();
			$date->setDate($matches[3], $matches[2], $matches[1]);
			return $date;
		}

		return false;
	}
}
