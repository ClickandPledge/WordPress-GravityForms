<?php

/**
* Options form input fields
*/
class GFCnpOptionsForm {

	public $AccountID;
	public $AccountGuid;
	public $useTest;
	
	public $available_cards;
	public $OrganizationInformation;
	public $ThankYouMessage;
	public $TermsCondition;
	
	public $isRecurring;
	public $Periods;
	public $RecurringMethods;
	public $maxrecurrings_Installment;
	public $maxrecurrings_Subscription;
	public $indefinite;
	public $email_customer;
	/**
	* initialise from form post, if posted
	*/
	public function __construct() {
		if (self::isFormPost()) {
			$this->AccountID = self::getPostValue('AccountID');
			$this->AccountGuid = self::getPostValue('AccountGuid');
			$this->useTest = self::getPostValue('useTest');
			$this->sslVerifyPeer = self::getPostValue('sslVerifyPeer');
			$available_cards = array();
			if(isset($_POST['Visa']))
			$available_cards['Visa'] = 'Visa';
			if(isset($_POST['American_Express']))
			$available_cards['American_Express'] = 'American Express';
			if(isset($_POST['Discover']))
			$available_cards['Discover'] = 'Discover';
			if(isset($_POST['MasterCard']))
			$available_cards['MasterCard'] = 'MasterCard';
			if(isset($_POST['JCB']))
			$available_cards['JCB'] = 'JCB';
			$this->available_cards = $available_cards;
			//print_r($this->available_cards);
			$this->email_customer = self::getPostValue('email_customer');			
			$this->OrganizationInformation = self::getPostValue('OrganizationInformation');
			$this->ThankYouMessage = self::getPostValue('ThankYouMessage');
			$this->TermsCondition = self::getPostValue('TermsCondition');
			
			//Recurring Variables
			$this->isRecurring = self::getPostValue('isRecurring');
			$Periods = array();
			if(isset($_POST['Week']))
			$Periods['Week'] = 'Week';
			if(isset($_POST['Weeks_2']))
			$Periods['Weeks_2'] = '2 Weeks';
			if(isset($_POST['Month']))
			$Periods['Month'] = 'Month';
			if(isset($_POST['Months_2']))
			$Periods['Months_2'] = '2 Months';
			if(isset($_POST['Quarter']))
			$Periods['Quarter'] = 'Quarter';
			if(isset($_POST['Months_6']))
			$Periods['Months_6'] = '6 Months';
			if(isset($_POST['Year']))
			$Periods['Year'] = 'Year';
			$this->Periods = $Periods;
			
			$RecurringMethods = array();
			if(isset($_POST['Installment']))
			$RecurringMethods['Installment'] = 'Installment';
			if(isset($_POST['Subscription']))
			$RecurringMethods['Subscription'] = 'Subscription';
			$this->RecurringMethods = $RecurringMethods;
			$this->maxrecurrings_Installment = self::getPostValue('maxrecurrings_Installment');
			$this->maxrecurrings_Subscription = self::getPostValue('maxrecurrings_Subscription');
			$this->indefinite = self::getPostValue('indefinite');
		}
	}

	/**
	* Is this web request a form post?
	*
	* Checks to see whether the HTML input form was posted.
	*
	* @return boolean
	*/
	public static function isFormPost() {
		return ($_SERVER['REQUEST_METHOD'] == 'POST');
	}

	/**
	* Read a field from form post input.
	*
	* Guaranteed to return a string, trimmed of leading and trailing spaces, sloshes stripped out.
	*
	* @return string
	* @param string $fieldname name of the field in the form post
	*/
	public static function getPostValue($fieldname) {
		return isset($_POST[$fieldname]) ? stripslashes(trim($_POST[$fieldname])) : '';
	}

	/**
	* Validate the form input, and return error messages.
	*
	* Return a string detailing error messages for validation errors discovered,
	* or an empty string if no errors found.
	* The string should be HTML-clean, ready for putting inside a paragraph tag.
	*
	* @return string
	*/
	public function validate() {
		
		$errmsg = '';
		if (strlen($this->AccountID) === 0)
			$errmsg .= "# Please enter the C&P Account ID.<br/>\n";
		if (strlen($this->AccountGuid) === 0)
			$errmsg .= "# Please enter the C&P API Account GUID.<br/>\n";
		$available_cards = array();
		if(isset($_POST['Visa']))
		$available_cards['Visa'] = 'Visa';
		if(isset($_POST['American_Express']))
		$available_cards['American_Express'] = 'American Express';
		if(isset($_POST['Discover']))
		$available_cards['Discover'] = 'Discover';
		if(isset($_POST['MasterCard']))
		$available_cards['MasterCard'] = 'MasterCard';
		if(isset($_POST['JCB']))
		$available_cards['JCB'] = 'JCB';
		if(count($available_cards) == 0) {
			$errmsg .= "# Please select at least Credit Card.<br/>\n";
		}
		if(!empty($errmsg))
			return '<font color="red">'.$errmsg.'</font>';
	}
}

/**
* Options admin
*/
class GFCnpOptionsAdmin {

	private $plugin;							// handle to the plugin object
	private $menuPage;							// slug for admin menu page
	private $scriptURL = '';
	private $frm;								// handle for the form validator

	/**
	* @param GFEwayPlugin $plugin handle to the plugin object
	* @param string $menuPage URL slug for this admin menu page
	*/
	public function __construct($plugin, $menuPage, $scriptURL) {
		$this->plugin = $plugin;
		$this->menuPage = $menuPage;
		$this->scriptURL = $scriptURL;

		wp_enqueue_script('jquery');
	}

	/**
	* process the admin request
	*/
	public function process() {

		$this->frm = new GFCnpOptionsForm();
		
		if ($this->frm->isFormPost()) {
			check_admin_referer('save', $this->menuPage . '_wpnonce');

			$errmsg = $this->frm->validate();
			
			if (empty($errmsg)) {
				$this->plugin->options['AccountID'] = $this->frm->AccountID;
				$this->plugin->options['AccountGuid'] = $this->frm->AccountGuid;
				$this->plugin->options['useTest'] = ($this->frm->useTest == 'Y');
				$available_cards = array();
				if(isset($_POST['Visa']))
				$available_cards['Visa'] = 'Visa';
				if(isset($_POST['American_Express']))
				$available_cards['American_Express'] = 'American Express';
				if(isset($_POST['Discover']))
				$available_cards['Discover'] = 'Discover';
				if(isset($_POST['MasterCard']))
				$available_cards['MasterCard'] = 'MasterCard';
				if(isset($_POST['JCB']))
				$available_cards['JCB'] = 'JCB';
				
				$this->plugin->options['available_cards'] = serialize($available_cards);
				$this->plugin->options['email_customer'] = $this->frm->email_customer;
				$this->plugin->options['OrganizationInformation'] = $this->frm->OrganizationInformation;
				$this->plugin->options['ThankYouMessage'] = $this->frm->ThankYouMessage;				
				$this->plugin->options['TermsCondition'] = $this->frm->TermsCondition;
				
				//Recurring Variables
				$this->plugin->options['isRecurring'] = $this->frm->isRecurring;
				$Periods = array();
				if(isset($_POST['Week']))
				$Periods['Week'] = 'Week';
				if(isset($_POST['Weeks_2']))
				$Periods['Weeks_2'] = '2 Weeks';
				if(isset($_POST['Month']))
				$Periods['Month'] = 'Month';
				if(isset($_POST['Months_2']))
				$Periods['Months_2'] = '2 Months';
				if(isset($_POST['Quarter']))
				$Periods['Quarter'] = 'Quarter';
				if(isset($_POST['Months_6']))
				$Periods['Months_6'] = '6 Months';
				if(isset($_POST['Year']))
				$Periods['Year'] = 'Year';
				$this->plugin->options['Periods'] = serialize($Periods);
				
				$RecurringMethods = array();
				if(isset($_POST['Installment']))
				$RecurringMethods['Installment'] = 'Installment';
				if(isset($_POST['Subscription']))
				$RecurringMethods['Subscription'] = 'Subscription';				
				$this->plugin->options['RecurringMethods'] = serialize($RecurringMethods);
				
				$this->plugin->options['maxrecurrings_Installment'] = $this->frm->maxrecurrings_Installment;
				$this->plugin->options['maxrecurrings_Subscription'] = $this->frm->maxrecurrings_Subscription;
				$this->plugin->options['indefinite'] = $this->frm->indefinite;
				
				update_option(GFCNP_PLUGIN_OPTIONS, $this->plugin->options);
				
				$this->saveErrorMessages();
				$this->plugin->showMessage(__('Options saved.'));
				//die('after message');
			}
			else {
				$this->plugin->showError($errmsg);
			}
		}
		else {
			// initialise form from stored options
			$this->frm->AccountID = $this->plugin->options['AccountID'];
			$this->frm->AccountGuid = $this->plugin->options['AccountGuid'];
			$this->frm->useTest = $this->plugin->options['useTest'] ? 'Y' : 'N';
						
			$this->frm->available_cards = unserialize($this->plugin->options['available_cards']);
			//print_r($this->frm->available_cards);
			//die('ffffffffff');
			$available_cards_all = array('Visa' => 'Visa', 'MasterCard' => 'MasterCard',  'Discover' => 'Discover', 'American_Express' => 'American Express',  'JCB' => 'JCB');
			$this->frm->available_cards_all = $available_cards_all;
			if(!count($this->frm->available_cards)) 
			{				
				$this->frm->available_cards = array();
				$this->frm->available_cards['Visa']		= 'Visa';
				$this->frm->available_cards['MasterCard']	= 'MasterCard';
				$this->frm->available_cards['Discover']	= 'Discover';
				$this->frm->available_cards['American_Express']	= 'American Express';			
				$this->frm->available_cards['JCB']	= 'JCB';				
			}
			$this->frm->email_customer = $this->plugin->options['email_customer'];
			$this->frm->OrganizationInformation = $this->plugin->options['OrganizationInformation'];
			$this->frm->ThankYouMessage = $this->plugin->options['ThankYouMessage'];
			$this->frm->TermsCondition = $this->plugin->options['TermsCondition'];
			
			//Recurring Variables
			$this->frm->isRecurring = $this->plugin->options['isRecurring'];
			/*
			$Periods = array('Week' => 'Week', 'Weeks_2' => '2 Weeks', 'Month' => 'Month', 'Months_2' => '2 Months', 'Quarter' => 'Quarter', 'Months_6' => '6 Months', 'Year' => 'Year');
			$this->frm->Periods = $Periods;
			*/
			$this->frm->Periods = unserialize($this->plugin->options['Periods']);
			if(!count($this->frm->Periods)) 
			{				
				foreach($Periods as $p => $v)
				$this->frm->Periods[$p]		= $v;		
			}
			$this->frm->RecurringMethods = unserialize($this->plugin->options['RecurringMethods']);
			if(!count($this->frm->RecurringMethods)) 
			{				
				$this->frm->RecurringMethods['Installment'] = 'Installment';
				$this->frm->RecurringMethods['Subscription'] = 'Subscription';					
			}
			$this->frm->maxrecurrings_Installment = $this->plugin->options['maxrecurrings_Installment'];
			$this->frm->maxrecurrings_Subscription = $this->plugin->options['maxrecurrings_Subscription'];
			$this->frm->indefinite = $this->plugin->options['indefinite'];
		}
		//echo '<pre>';
//print_r($this->frm);
		require GFCNP_PLUGIN_ROOT . 'views/admin-settings.php';
	}

	/**
	* save error messages
	*/
	private function saveErrorMessages() {
		$errNames = array (
			GFCNP_ERROR_ALREADY_SUBMITTED,
			GFCNP_ERROR_NO_AMOUNT,
			GFCNP_ERROR_REQ_CARD_HOLDER,
			GFCNP_ERROR_REQ_CARD_NAME,
			GFCNP_ERROR_CNP_FAIL,
		);
		foreach ($errNames as $errName) {
			$msg = $this->frm->getPostValue($errName);
			delete_option($errName);
			if (!empty($msg)) {
				add_option($errName, $msg, '', 'no');
			}
		}
	}
}
