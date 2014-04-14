<?php

/**
* with thanks to Travis Smith's excellent tutorial:
* http://wpsmith.net/2011/plugins/how-to-create-a-custom-form-field-in-gravity-forms-with-a-terms-of-service-form-field-example/
*/
class GFCnpSKUField {

	var $p;
	/**
	* @param GFEwayPlugin $plugin
	*/
	public function __construct($plugin) {
		$this->p = $plugin;
		
		// WordPress script hooks -- NB: must happen after Gravity Forms registers scripts
		add_action('wp_enqueue_scripts', array($this, 'registerScripts'), 20);
		add_action('admin_enqueue_scripts', array($this, 'registerScripts'), 20);
		
		add_action('gform_editor_js', array($this, 'gformEditorJS'));
		add_action('gform_field_standard_settings', array($this, 'gformFieldStandardSettings'), 10, 2);
		add_filter('gform_pre_validation', array($this, 'gformPreValidation'));
add_filter('gform_pre_submission', array($this, 'gformPreSubmit'));		
	}
	
	/**
	* load custom script for editor form
	*/
	public function gformEditorJS() {
		$version = GFCNP_PLUGIN_VERSION;
		$min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
		echo "<script src=\"{$this->plugin->urlBase}js/admin-sku$min.js?v=$version\"></script>\n";
	}
	
	/**
	* add custom fields to form editor
	* @param integer $position
	* @param integer $form_id
	*/
	public function gformFieldStandardSettings($position, $form_id) {
		//echo $position.'##SKU';
		//die('SKU');
		
		// add inputs for labels right after the field label input
		if ($position == 25) {
			?>
			<li class="gfcnpsku_setting field_setting">
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
					
					<div id="indefinite_div">
					<input type="checkbox" id="indefinite" value="indefinite" onclick="GFCnpRecurring.FieldSet2(this)">&nbsp;Allow indefinite recurring<br>
					</div>					
			</li>
			<?php
		}
	}
	
	/**
	* grab values and concatenate into a string before submission is accepted
	* @param array $form
	*/
	public function gformPreSubmit($form) {
	  echo '<pre>';
  print_r($form["fields"]);
  die();
	}
	/**
	* prime the inputs that will be checked by standard validation tests,
	* e.g. so that "required" fields don't fail
	* @param array $form
	* @return array
	*/
	public function gformPreValidation($form) {

		foreach($form["fields"] as $field) {
			if ($field['type'] == 'product') {
				$recurring = self::getPost($field['id']);
				$_POST["input_{$field['id']}"] = serialize($recurring);
			}
		}

		return $form;
	}
}
