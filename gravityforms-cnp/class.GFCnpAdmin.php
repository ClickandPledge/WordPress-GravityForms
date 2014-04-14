<?php

/**
* class for admin screens
*/
class GFCnpAdmin {

	public $settingsURL;

	private $plugin;

	/**
	* @param GFEwayPlugin $plugin
	*/
	public function __construct($plugin) {
		$this->plugin = $plugin;

		// handle change in settings pages
		if (class_exists('GFCommon')) {
			if (version_compare(GFCommon::$version, '1.6.99999', '<')) {
				// pre-v1.7 settings
				$this->settingsURL = admin_url('admin.php?page=gf_settings&addon=C%26P+Payments');
			}
			else {
				// post-v1.7 settings
				$this->settingsURL = admin_url('admin.php?page=gf_settings&subview=C%26P+Payments');
			}
		}

		// handle admin init action
		add_action('admin_init', array($this, 'adminInit'));

		// add GravityForms hooks
		add_filter('gform_currency_setting_message', array($this, 'gformCurrencySettingMessage'));
		add_action('gform_payment_status', array($this, 'gformPaymentStatus'), 10, 3);
		add_action('gform_after_update_entry', array($this, 'gformAfterUpdateEntry'), 10, 2);
		add_action("gform_entry_info", array($this, 'gformEntryInfo'), 10, 2);

		// hook for showing admin messages
		add_action('admin_notices', array($this, 'actionAdminNotices'));

		// add action hook for adding plugin action links
		add_action('plugin_action_links_' . GFCNP_PLUGIN_NAME, array($this, 'addPluginActionLinks'));

		// hook for adding links to plugin info
		add_filter('plugin_row_meta', array($this, 'addPluginDetailsLinks'), 10, 2);

		
	}

	/**
	* test whether GravityForms plugin is installed and active
	* @return boolean
	*/
	public static function isGfActive() {
		return class_exists('RGForms');
	}

	/**
	* handle admin init action
	*/
	public function adminInit() {
		if (isset($_GET['page'])) {
			switch ($_GET['page']) {
				case 'gf_settings':
					// add our settings page to the Gravity Forms settings menu
					RGForms::add_settings_page('C&P Payments', array($this, 'optionsAdmin'));
					break;
			}
		}
	}


	/**
	* show admin messages
	*/
	public function actionAdminNotices() {
		if (!self::isGfActive()) {
			$this->plugin->showError('Gravity Forms Click & Pledge requires <a href="http://www.gravityforms.com/">Gravity Forms</a> to be installed and activated.');
		}
	}

	/**
	* action hook for adding plugin action links
	*/
	public function addPluginActionLinks($links) {
		// add settings link, but only if GravityForms plugin is active
		if (self::isGfActive()) {
			$settings_link = sprintf('<a href="%s">%s</a>', $this->settingsURL, __('Settings'));
			array_unshift($links, $settings_link);
		}

		return $links;
	}

	/**
	* action hook for adding plugin details links
	*/
	public static function addPluginDetailsLinks($links, $file) {
		if ($file == GFCNP_PLUGIN_NAME) {
			$links[] = '<a href="http://wordpress.org/support/plugin/gravityforms-cnp">' . __('Get help') . '</a>';
		}

		return $links;
	}

	/**
	* action hook for showing currency setting message
	* @param array $menus
	* @return array
	*/
	public function gformCurrencySettingMessage() {
		echo "<div class='gform_currency_message'>NB: Gravity Forms Click & Pledge only supports 'USD', 'EUR', 'CAD', 'GBP'.</div>\n";
	}

	/**
	* action hook for building the entry details view
	* @param int $form_id
	* @param array $lead
	*/
	public function gformEntryInfo($form_id, $lead) {
		$payment_gateway = gform_get_meta($lead['id'], 'payment_gateway');
		if ($payment_gateway == 'gfcnp') {
			$authcode = gform_get_meta($lead['id'], 'authcode');
			if ($authcode) {
				echo 'Auth Code: ', esc_html($authcode), "<br /><br />\n";
			}
		}
	}

	/**
	* action hook for processing admin menu item
	*/
	public function optionsAdmin() {
		$admin = new GFCnpOptionsAdmin($this->plugin, 'gfcnp-options', $this->settingsURL);
		$admin->process();
	}

	/**
	* allow edits to payment status
	* @param string $payment_status
	* @param array $form
	* @param array $lead
	* @return string
	*/
    public function gformPaymentStatus($payment_status, $form, $lead) {
		// make sure payment is not Approved, and that we're editing the lead
		if ($payment_status == 'Approved' || strtolower(rgpost('save')) <> 'edit') {
			return $payment_status;
		}

		// make sure payment is one of ours (probably)
		$payment_gateway = gform_get_meta($lead['id'], 'payment_gateway');
		if ((empty($payment_gateway) && $this->plugin->hasFieldType($form['fields'], 'creditcard')) || $payment_gateway != 'gfcnp') {
			return $payment_status;
		}

		// make sure payment isn't a recurring payment
		if ($this->plugin->hasFieldType($form['fields'], GFCNP_FIELD_RECURRING)) {
			return $payment_status;
		}

		// create drop down for payment status
		//~ $payment_string = gform_tooltip("paypal_edit_payment_status","",true);
		$input = <<<HTML
<select name="payment_status">
 <option value="$payment_status" selected="selected">$payment_status</option>
 <option value="Approved">Approved</option>
</select>

HTML;

		return $input;
    }

	/**
	* update payment status if it has changed
	* @param array $form
	* @param int $lead_id
	*/
	public function gformAfterUpdateEntry($form, $lead_id) {
		// make sure we have permission
		check_admin_referer('gforms_save_entry', 'gforms_save_entry');

		// check that save action is for update
		if (strtolower(rgpost("save")) <> 'update')
			return;

		// make sure payment is one of ours (probably)
		$payment_gateway = gform_get_meta($lead_id, 'payment_gateway');
		if ((empty($payment_gateway) && $this->plugin->hasFieldType($form['fields'], 'creditcard')) || $payment_gateway != 'gfcnp') {
			return;
		}

		// make sure we have a new payment status
		$payment_status = rgpost('payment_status');
		if (empty($payment_status)) {
			return;
		}

		// update payment status
		$lead = GFFormsModel::get_lead($lead_id);
		$lead["payment_status"] = $payment_status;

		GFFormsModel::update_lead($lead);
	}
}
