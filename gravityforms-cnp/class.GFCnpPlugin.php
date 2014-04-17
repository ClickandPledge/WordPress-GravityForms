<?php
/**
* custom exception types
*/
class GFCnpException extends Exception {}
class GFCnpCurlException extends Exception {}

/**
* class for managing the plugin
*/
class GFCnpPlugin {
	public $urlBase;									// string: base URL path to files in plugin
	public $options;									// array of plugin options
	public $responsecodes;
	
	protected $acceptedCards;							// hash map of accepted credit cards
	protected $txResult = null;							// results from credit card payment transaction
	protected $formHasCcField = false;					// true if current form has credit card field
	
	/**
	* static method for getting the instance of this singleton object
	*
	* @return GFEwayPlugin
	*/
	public static function getInstance() {
		static $instance = NULL;

		if (is_null($instance)) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	* initialise plugin
	*/
	private function __construct() {
		// grab options, setting new defaults for any that are missing
		$this->initOptions();

		// record plugin URL base
		$this->urlBase = plugin_dir_url(__FILE__);
		
		//print_r(unserialize($this->options['available_cards']));
		// filter the cards array to just Visa, MasterCard and Amex

		foreach(unserialize($this->options['available_cards']) as $card => $value) {
		if($card == 'Visa') $this->acceptedCards['visa'] = 1;
		if($card == 'American_Express') $this->acceptedCards['amex'] = 1;
		if($card == 'Discover') $this->acceptedCards['discover'] = 1;
		if($card == 'MasterCard') $this->acceptedCards['mastercard'] = 1;
		if($card == 'JCB') $this->acceptedCards['jcb'] = 1;
		//$this->acceptedCards = array('amex' => 1, 'mastercard' => 1, 'visa' => 1, 'discover' => 1, 'jcb' => 1);
		}

		add_action('init', array($this, 'init'));
	}

	/**
	* initialise plug-in options, handling undefined options by setting defaults
	*/
	protected function initOptions() {
		$defaults = array (
			'AccountID' => '',
			'AccountGuid' => '',
			'useTest' => true,
			'sslVerifyPeer' => true,
			
			'available_cards' => serialize(array('Visa' => 'Visa', 'American_Express' => 'American Express', 'Discover' => 'Discover', 'MasterCard' => 'MasterCard', 'JCB' => 'JCB')),
			'OrganizationInformation' => '',
			'ThankYouMessage' => '',
			'TermsCondition' => '',
			'isRecurring' => '0',
		);
		$this->responsecodes = array(2054=>'Total amount is wrong',2055=>'AccountGuid is not valid',2056=>'AccountId is not valid',2057=>'Username is not valid',2058=>'Password is not valid',2059=>'Invalid recurring parameters',2060=>'Account is disabled',2101=>'Cardholder information is null',2102=>'Cardholder information is null',2103=>'Cardholder information is null',2104=>'Invalid billing country',2105=>'Credit Card number is not valid',2106=>'Cvv2 is blank',2107=>'Cvv2 length error',2108=>'Invalid currency code',2109=>'CreditCard object is null',2110=>'Invalid card type ',2111=>'Card type not currently accepted',2112=>'Card type not currently accepted',2210=>'Order item list is empty',2212=>'CurrentTotals is null',2213=>'CurrentTotals is invalid',2214=>'TicketList lenght is not equal to quantity',2215=>'NameBadge lenght is not equal to quantity',2216=>'Invalid textonticketbody',2217=>'Invalid textonticketsidebar',2218=>'Invalid NameBadgeFooter',2304=>'Shipping CountryCode is invalid',2305=>'Shipping address missed',2401=>'IP address is null',2402=>'Invalid operation',2501=>'WID is invalid',2502=>'Production transaction is not allowed. Contact support for activation.',2601=>'Invalid character in a Base-64 string',2701=>'ReferenceTransaction Information Cannot be NULL',2702=>'Invalid Refrence Transaction Information',2703=>'Expired credit card',2805=>'eCheck Account number is invalid',2807=>'Invalid payment method',2809=>'Invalid payment method',2811=>'eCheck payment type is currently not accepted',2812=>'Invalid check number',1001=>'Internal error. Retry transaction',1002=>'Error occurred on external gateway please try again',2001=>'Invalid account information',2002=>'Transaction total is not correct',2003=>'Invalid parameters',2004=>'Document is not a valid xml file',2005=>'OrderList can not be empty',3001=>'Invalid RefrenceTransactionID',3002=>'Invalid operation for this transaction',4001=>'Fraud transaction',4002=>'Duplicate transaction',5001=>'Declined (general)',5002=>'Declined (lost or stolen card)',5003=>'Declined (fraud)',5004=>'Declined (Card expired)',5005=>'Declined (Cvv2 is not valid)',5006=>'Declined (Insufficient fund)',5007=>'Declined (Invalid credit card number)');
		$this->options = (array) get_option(GFCNP_PLUGIN_OPTIONS);

		if (count(array_diff_assoc($defaults, $this->options)) > 0) {
			$this->options = array_merge($defaults, $this->options);
			update_option(GFCNP_PLUGIN_OPTIONS, $this->options);
		}

	}

	/**
	* handle the plugin's init action
	*/
	public function init() {

		// do nothing if Gravity Forms isn't enabled
		if (class_exists('GFCommon')) {
			// hook into Gravity Forms to enable credit cards and trap form submissions
			add_filter('gform_pre_render', array($this, 'gformPreRenderSniff'));
			add_filter('gform_admin_pre_render', array($this, 'gformPreRenderSniff'));
			add_action('gform_enable_credit_card_field', '__return_true');		// just return true to enable CC fields
			add_filter('gform_creditcard_types', array($this, 'gformCCTypes'));
			add_filter('gform_currency', array($this, 'gformCurrency'));
			add_filter('gform_validation', array($this, 'gformValidation'));
			add_action('gform_after_submission', array($this, 'gformAfterSubmission'), 10, 2);
			add_filter('gform_custom_merge_tags', array($this, 'gformCustomMergeTags'), 10, 4);
			add_filter('gform_replace_merge_tags', array($this, 'gformReplaceMergeTags'), 10, 7);
			
			//new GFCnpSKUField($this);
			add_action('gform_field_standard_settings', array($this, 'gformAddsku'));
            //do_action("gform_field_standard_settings", 1, $form_id);                             
			//print_r($this->options);
			//if($this->options['isRecurring'] == 1) 
			{
			// hook into Gravity Forms to handle Recurring Payments custom field
			new GFCnpRecurringField($this);
			}
		}

		if (is_admin()) {
			// kick off the admin handling
			new GFCnpAdmin($this);
		}
		
		//SKU field test
		//new GFCnpSKUField($this);
	}

	public function gformAddsku($form) {
		
	}
	
	/**
	* check current form for information
	* @param array $form
	* @return array
	*/
	public function gformPreRenderSniff($form) {
		// test whether form has a credit card field
		$this->formHasCcField = self::hasFieldType($form['fields'], 'creditcard');

		return $form;
	}

	/**
	* process a form validation filter hook; if last page and has credit card field and total, attempt to bill it
	* @param array $data an array with elements is_valid (boolean) and form (array of form elements)
	* @return array
	*/
	public function gformValidation($data) {
		
	// make sure all other validations passed
		if ($data['is_valid']) {
			$formData = new GFCnpFormData($data['form']);
			
			// make sure form hasn't already been submitted / processed			
			if ($this->hasFormBeenProcessed($data['form'])) 
			//if($this->is_submitted)
			{
				
				$data['is_valid'] = false;
				
				$formData->ccField['failed_validation'] = true;
				$formData->ccField['validation_message'] = $this->getErrMsg(GFCNP_ERROR_ALREADY_SUBMITTED);
				
			}

			// make that this is the last page of the form and that we have a credit card field and something to bill
			// and that credit card field is not hidden (which indicates that payment is being made another way)
			else if (!$formData->isCcHidden() && $formData->isLastPage() && is_array($formData->ccField)) {
				
				if (!$formData->hasPurchaseFields()) {
					$data['is_valid'] = false;
					$formData->ccField['failed_validation'] = true;
					$formData->ccField['validation_message'] = $this->getErrMsg(GFCNP_ERROR_NO_AMOUNT);
				}
				else {								
					$hasproducts = false;
					foreach ($data['form']['fields'] as $field) {
						if (GFCommon::is_product_field($field['type']) || $field['type'] == 'donation') {
							$hasproducts = true;							
						}
					}
					
					if($formData->creditcardCount > 1)
					{
						$errmsg = "Error in the form. Form should have only one Credit card field. Please contact administrator\n";
						$formData->ccField['validation_message'] = $errmsg;
						$data['is_valid'] = false;
						$formData->ccField['failed_validation'] = true;
					}
					if($formData->shippingCount > 1)
					{
						$errmsg = "Error in the form. Form should have only one Shipping field. Please contact administrator\n";
						$formData->ccField['validation_message'] = $errmsg;
						$data['is_valid'] = false;
						$formData->ccField['failed_validation'] = true;
					}
					if($formData->recurringCount > 1)
					{
						$errmsg = "Error in the form. Form should have only one recurring field. Please contact administrator\n";
						$formData->ccField['validation_message'] = $errmsg;
						$data['is_valid'] = false;
						$formData->ccField['failed_validation'] = true;
					}
					if (count($formData->productdetails) == 0 && $hasproducts) 
					{
						$errmsg = "Please select at least one product.\n";
						$formData->ccField['validation_message'] = $errmsg;
						$data['is_valid'] = false;
						$formData->ccField['failed_validation'] = true;
					}
					//Here we need to validate quantity field					
					if ($formData->amount == 0 && count($formData->productdetails) == 0 && $hasproducts) 
					{
						$errmsg = "Please select at least one product.\n";
						$formData->ccField['validation_message'] = $errmsg;
						$data['is_valid'] = false;
						$formData->ccField['failed_validation'] = true;
					}
					
					
					// only check credit card details if we've got something to bill
					//if ($formData->total > 0) 
					if ( count($formData->productdetails) && $hasproducts ) 
					{
						// check for required fields
						$required = array(
							'ccName' => $this->getErrMsg(GFCNP_ERROR_REQ_CARD_HOLDER),
							'ccNumber' => $this->getErrMsg(GFCNP_ERROR_REQ_CARD_NAME),
						);
						foreach ($required as $name => $message) {
							if (empty($formData->$name)) {
								$data['is_valid'] = false;
								$formData->ccField['failed_validation'] = true;
								if (!empty($formData->ccField['validation_message']))
									$formData->ccField['validation_message'] .= '<br />';
								$formData->ccField['validation_message'] .= $message;
							}
						}
						
						// if no errors, try to bill it
						if ($data['is_valid']) {
							$data = $this->processSinglePayment($data, $formData);
							
						}
					}
				}
			}

			// if errors, send back to credit card page
			if (!$data['is_valid']) {
				GFFormDisplay::set_current_page($data['form']['id'], $formData->ccField['pageNumber']);
			}
		}

		return $data;
	}

	/**
	* check whether this form entry's unique ID has already been used; if so, we've already done a payment attempt.
	* @param array $form
	* @return boolean
	*/
	protected function hasFormBeenProcessed($form) {
		global $wpdb;
		
		$unique_id = RGFormsModel::get_form_unique_id($form['id']);

		$sql = "select lead_id from {$wpdb->prefix}rg_lead_meta where meta_key='gfcnp_unique_id' and meta_value = '\"%s\"'";
		$lead_id = $wpdb->get_var($wpdb->prepare($sql, $unique_id));
		return !empty($lead_id);
	}

	/**
	* get customer ID to use with payment gateway
	* @return string
	*/
	protected function getCustomerID() {
		return $this->options;
	}

	/**
	* process regular one-off payment
	* @param array $data an array with elements is_valid (boolean) and form (array of form elements)
	* @param GFEwayFormData $formData pre-parsed data from $data
	* @return array
	*/
	protected function processSinglePayment($data, $formData) {

		try {
			$cnp = new GFCnpPayment($this->getCustomerID(), !$this->options['useTest'], $formData);
			
			$cnp->sslVerifyPeer = $this->options['sslVerifyPeer'];
			if (empty($formData->firstName) && empty($formData->lastName)) {
				$cnp->lastName = $formData->ccName;				// pick up card holder's name for last name
			}
			else {
				$cnp->firstName = $formData->firstName;
				$cnp->lastName = $formData->lastName;
			}
			$cnp->cardHoldersName = $formData->ccName;
			$cnp->cardNumber = $formData->ccNumber;
			$cnp->cardExpiryMonth = $formData->ccExpMonth;
			$cnp->cardExpiryYear = $formData->ccExpYear;
			$cnp->emailAddress = $formData->email;
			$cnp->address = $formData->address;
			$cnp->postcode = $formData->postcode;
			$cnp->cardVerificationNumber = $formData->ccCVN;
			
			// To get the country code
			if (isset($formData->address_country) && $formData->address_country != '') {
				//$cnp->customerCountryCode = GFCommon::get_country_code($formData->address_country);
				$countries = simplexml_load_file( WP_PLUGIN_URL.DIRECTORY_SEPARATOR.plugin_basename( dirname(__FILE__)).DIRECTORY_SEPARATOR.'Countries.xml' );
				
				foreach( $countries as $country ){
					if( $country->attributes()->Name == $formData->address_country ){
						$billing_country_id = $country->attributes()->Code;
						break;
					} 
				}
				
				if($billing_country_id)
				{
				$cnp->customerCountryCode = $billing_country_id;
				}
			}

			$cnp->amount = $formData->total;
			$response = $cnp->processPayment();
			
			$ResultCode = $response->OperationResult->ResultCode;
			$transation_number = $response->OperationResult->TransactionNumber;
			$VaultGUID = $response->OperationResult->VaultGUID; 
			if ($ResultCode == '0') {
				// transaction was successful, so record transaction number and continue
				$this->txResult = array (
					'transaction_id' => $VaultGUID,
					'payment_status' => 'Approved',
					'payment_date' => date('Y-m-d H:i:s'),
					'payment_amount' => $cnp->amount,
					'transaction_type' => 1,
					'authcode' => $VaultGUID,
				);
				
			}
			else {
				$data['is_valid'] = false;
				$formData->ccField['failed_validation'] = true;
				if( in_array( $ResultCode, array( 2051,2052,2053 ) ) )
				{
					$AdditionalInfo = $response->OperationResult->AdditionalInfo;
				}
				else
				{
					if( isset( $this->responsecodes[$ResultCode] ) )
					{
						$AdditionalInfo = $this->responsecodes[$ResultCode];
					}
					else
					{
						$AdditionalInfo = 'Unknown error';
					}
				}
				//print_r($this->responsecodes);
				//die();
				$formData->ccField['validation_message'] = nl2br($this->getErrMsg(GFCNP_ERROR_FAIL) . ":\n{$AdditionalInfo}");
				$this->txResult = array (
					'payment_status' => 'Failed',
				);
			}
		}
		catch (GFCnpException $e) {
			
			$data['is_valid'] = false;
			$this->txResult = array (
				'payment_status' => 'Failed',
			);
			$formData->ccField['failed_validation'] = true;
			$formData->ccField['validation_message'] = nl2br($this->getErrMsg(GFCNP_ERROR_FAIL) . ":\n{$e->getMessage()}");
			$errmsg = nl2br($this->getErrMsg(GFCNP_ERROR_FAIL) . ":\n{$e->getMessage()}");
		}

		return $data;
	}

	/**
	* save the transaction details to the entry after it has been created
	* @param array $data an array with elements is_valid (boolean) and form (array of form elements)
	* @return array
	*/
	public function gformAfterSubmission($entry, $form) {
		
		$formData = new GFCnpFormData($form);
		
		if (!empty($this->txResult)) {
			foreach ($this->txResult as $key => $value) {
				$entry[$key] = $value;
			}
			RGFormsModel::update_lead($entry);

			// record entry's unique ID in database
			$unique_id = RGFormsModel::get_form_unique_id($form['id']);

			gform_update_meta($entry['id'], 'gfcnp_unique_id', $unique_id);
			
			
			// record payment gateway
			gform_update_meta($entry['id'], 'payment_gateway', 'gfcnp');
			//global $wpdb;
			$formData->is_valid = 0;
			
		}
	}

	/**
	* add custom merge tags
	* @param array $merge_tags
	* @param int $form_id
	* @param array $fields
	* @param int $element_id
	* @return array
	*/
	
	public function gformCustomMergeTags($merge_tags, $form_id, $fields, $element_id) {
		if ($fields && $this->hasFieldType($fields, 'creditcard')) {
			$merge_tags[] = array('label' => 'Transaction ID', 'tag' => '{transaction_id}');
			$merge_tags[] = array('label' => 'Auth Code', 'tag' => '{authcode}');
			$merge_tags[] = array('label' => 'Payment Amount', 'tag' => '{payment_amount}');
			$merge_tags[] = array('label' => 'Payment Status', 'tag' => '{payment_status}');
		}

		return $merge_tags;
	}

	/**
	* replace custom merge tags
	* @param string $text
	* @param array $form
	* @param array $lead
	* @param bool $url_encode
	* @param bool $esc_html
	* @param bool $nl2br
	* @param string $format
	* @return string
	*/
	
	public function gformReplaceMergeTags($text, $form, $lead, $url_encode, $esc_html, $nl2br, $format) {
		if ($this->hasFieldType($form['fields'], 'creditcard')) {
			if (is_null($this->txResult)) {
				// lead loaded from database, get values from lead meta
				$transaction_id = isset($lead['transaction_id']) ? $lead['transaction_id'] : '';
				$payment_amount = isset($lead['payment_amount']) ? $lead['payment_amount'] : '';
				$payment_status = isset($lead['payment_status']) ? $lead['payment_status'] : '';
				$authcode = (string) gform_get_meta($lead['id'], 'authcode');
				$beagle_score = (string) gform_get_meta($lead['id'], 'beagle_score');
			}
			else {
				// lead not yet saved, get values from transaction results
				$transaction_id = isset($this->txResult['transaction_id']) ? $this->txResult['transaction_id'] : '';
				$payment_amount = isset($this->txResult['payment_amount']) ? $this->txResult['payment_amount'] : '';
				$payment_status = isset($this->txResult['payment_status']) ? $this->txResult['payment_status'] : '';
				$authcode = isset($this->txResult['authcode']) ? $this->txResult['authcode'] : '';
				$beagle_score = isset($this->txResult['beagle_score']) ? $this->txResult['beagle_score'] : '';
			}

			$tags = array (
				'{transaction_id}',
				'{payment_amount}',
				'{payment_status}',
				'{authcode}',
				'{beagle_score}',
			);
			$values = array (
				$transaction_id,
				$payment_amount,
				$payment_status,
				$authcode,
				$beagle_score,
			);

			$text = str_replace($tags, $values, $text);
		}

		return $text;
	}

	/**
	* tell Gravity Forms what credit cards we can process
	* @param array $cards
	* @return array
	*/
	public function gformCCTypes($cards) {
		$new_cards = array();
		foreach ($cards as $i => $card) {
			if (isset($this->acceptedCards[$card['slug']])) {
				$new_cards[] = $card;
			}
		}
		return $new_cards;
	}

	/**
	* tell Gravity Forms what currencies we can process
	* @param string $currency
	* @return string
	*/
	public function gformCurrency($currency) {
		// return the currency if current form has a CC field
		if ($this->formHasCcField) {
			$currency = $currency;
		}

		return $currency;
	}

	/**
	* check form to see if it has a field of specified type
	* @param array $fields array of fields
	* @param string $type name of field type
	* @return boolean
	*/
	public static function hasFieldType($fields, $type) {
		if (is_array($fields)) {
			foreach ($fields as $field) {
				if (RGFormsModel::get_input_type($field) == $type)
					return true;
			}
		}
		return false;
	}

	/**
	* get nominated error message, checking for custom error message in WP options
	* @param string $errName the fixed name for the error message (a constant)
	* @param boolean $useDefault whether to return the default, or check for a custom message
	* @return string
	*/
	public function getErrMsg($errName, $useDefault = false) {
		static $messages = array (
			GFCNP_ERROR_ALREADY_SUBMITTED		=> 'Payment already submitted and processed - please close your browser window',
			GFCNP_ERROR_NO_AMOUNT				=> 'This form has credit card fields, but no products or totals',
			GFCNP_ERROR_REQ_CARD_HOLDER		=> 'Card holder name is required for credit card processing',
			GFCNP_ERROR_REQ_CARD_NAME			=> 'Card number is required for credit card processing',
			GFCNP_ERROR_FAIL				=> 'Error processing card transaction',
		);

		// default
		$msg = isset($messages[$errName]) ? $messages[$errName] : 'Unknown error';

		// check for custom message
		if (!$useDefault) {
			$msg = get_option($errName, $msg);
		}

		return $msg;
	}

	/**
	* send data via cURL and return result
	* @param string $url
	* @param string $data
	* @param bool $sslVerifyPeer whether to validate the SSL certificate
	* @return string $response
	* @throws GFEwayCurlException
	*/
	public static function curlSendRequest($url, $data, $sslVerifyPeer = true) {
		// send data via HTTPS and receive response
		$response = wp_remote_post($url, array(
			'user-agent' => 'Gravity Forms Click & Pledge',
			'sslverify' => $sslVerifyPeer,
			'timeout' => 60,
			'headers' => array('Content-Type' => 'text/xml; charset=utf-8'),
			'body' => $data,
		));

//~ error_log(__METHOD__ . "\n" . print_r($response,1));

		if (is_wp_error($response)) {
			throw new GFCnpCurlException($response->get_error_message());
		}

		return $response['body'];
	}

	/**
	* get the customer's IP address dynamically from server variables
	* @return string
	*/
	public static function getCustomerIP() {
		// if test mode and running on localhost, then kludge to an Aussie IP address
		$plugin = self::getInstance();
		if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] == '127.0.0.1' && $plugin->options['useTest']) {
			return '210.1.199.10';
		}

		// check for remote address, ignore all other headers as they can be spoofed easily
		if (isset($_SERVER['REMOTE_ADDR']) && self::isIpAddress($_SERVER['REMOTE_ADDR'])) {
			return $_SERVER['REMOTE_ADDR'];
		}

		return '';
	}

	/**
	* check whether a given string is an IP address
	* @param string $maybeIP
	* @return bool
	*/
	protected static function isIpAddress($maybeIP) {
		if (function_exists('inet_pton')) {
			// check for IPv4 and IPv6 addresses
			return !!inet_pton($maybeIP);
		}

		// just check for IPv4 addresses
		return !!ip2long($maybeIP);
	}

	/**
	* display a message (already HTML-conformant)
	* @param string $msg HTML-encoded message to display inside a paragraph
	*/
	public static function showMessage($msg) {
		echo "<div class='updated fade'><p><strong>$msg</strong></p></div>\n";
	}

	/**
	* display an error message (already HTML-conformant)
	* @param string $msg HTML-encoded message to display inside a paragraph
	*/
	public static function showError($msg) {
		echo "<div class='error'><p><strong>$msg</strong></p></div>\n";
	}
}
