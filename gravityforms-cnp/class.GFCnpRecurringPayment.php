<?php
/**
* Classes for dealing with eWAY recurring payments
*
* NB: for testing, the only account number recognised is '87654321' and the only card number seen as valid is '4444333322221111'
*/

/**
* Class for dealing with an eWAY recurring payment request
*/
class GFEwayRecurringPayment {
	// environment / website specific members
	/**
	* default FALSE, use eWAY sandbox unless set to TRUE
	* @var boolean
	*/
	public $isLiveSite;

	/**
	* default TRUE, whether to validate the remote SSL certificate
	* @var boolean
	*/
	public $sslVerifyPeer;

	// payment specific members
	/**
	* account name / email address at eWAY
	* @var string max. 8 characters
	*/
	public $accountID;

	/**
	* customer's title
	* @var string max. 20 characters
	*/
	public $title;

	/**
	* customer's first name
	* @var string max. 50 characters
	*/
	public $firstName;

	/**
	* customer's last name
	* @var string max. 50 characters
	*/
	public $lastName;

	/**
	* customer's email address
	* @var string max. 50 characters
	*/
	public $emailAddress;

	/**
	* customer's address
	* @var string max. 255 characters
	*/
	public $address;

	/**
	* customer's suburb/city/town
	* @var string max. 50 characters
	*/
	public $suburb;

	/**
	* customer's state/province
	* @var string max. 50 characters
	*/
	public $state;

	/**
	* customer's postcode
	* @var string max. 6 characters
	*/
	public $postcode;

	/**
	* customer's country
	* @var string max. 50 characters
	*/
	public $country;

	/**
	* customer's phone number
	* @var string max. 20 characters
	*/
	public $phone;

	/**
	* customer's comments
	* @var string max. 255 characters
	*/
	public $customerComments;

	/**
	* an customer reference to track by (NB: see also invoiceReference)
	* @var string max. 20 characters
	*/
	public $customerReference;

	/**
	* an invoice reference to track by
	* @var string max. 50 characters
	*/
	public $invoiceReference;

	/**
	* description of what is being purchased / paid for
	* @var string max. 10000 characters
	*/
	public $invoiceDescription;

	/**
	* name on credit card
	* @var string max. 50 characters
	*/
	public $cardHoldersName;

	/**
	* credit card number, with no spaces
	* @var string max. 20 characters
	*/
	public $cardNumber;

	/**
	* month of expiry, numbered from 1=January
	* @var integer max. 2 digits
	*/
	public $cardExpiryMonth;

	/**
	* year of expiry
	* @var integer will be truncated to 2 digits, can accept 4 digits
	*/
	public $cardExpiryYear;

	/**
	* total amount of intial payment, in dollars and cents as a floating-point number (will be converted to just cents for transmission)
	* may be 0 (i.e. nothing upfront, only on recurring billings)
	* @var float
	*/
	public $amountInit;

	/**
	* total amount of recurring payment, in dollars and cents as a floating-point number (will be converted to just cents for transmission)
	* @var float
	*/
	public $amountRecur;

	/**
	* the date of the initial payment (e.g. today, when the customer signed up)
	* @var DateTime
	*/
	public $dateInit;

	/**
	* the date of the first recurring payment
	* @var DateTime
	*/
	public $dateStart;

	/**
	* the date of the last recurring payment
	* @var DateTime
	*/
	public $dateEnd;

	/**
	* size of the interval between recurring payments (be it days, months, years, etc.) in range 1-31
	* @var integer
	*/
	public $intervalSize;

	/**
	* type of interval (see interval type constants below)
	* @var integer
	*/
	public $intervalType;

	/** interval type Days */
	const DAYS = 1;
	/** interval type Weeks */
	const WEEKS = 2;
	/** interval type Months */
	const MONTHS = 3;
	/** interval type Years */
	const YEARS = 4;

	/** host for the eWAY Real Time API in the developer sandbox environment */
	const REALTIME_API_SANDBOX = 'https://www.eway.com.au/gateway/rebill/test/Upload_test.aspx';
	/** host for the eWAY Real Time API in the production environment */
	const REALTIME_API_LIVE = 'https://www.eway.com.au/gateway/rebill/upload.aspx';

	/**
	* populate members with defaults, and set account and environment information
	*
	* @param string $accountID eWAY account ID
	* @param boolean $isLiveSite running on the live (production) website
	*/
	public function __construct($accountID, $isLiveSite = FALSE) {
		$this->sslVerifyPeer = TRUE;
		$this->isLiveSite = $isLiveSite;
		$this->accountID = $accountID;
	}

	/**
	* process a payment against eWAY; throws exception on error with error described in exception message.
	*/
	public function processPayment() {
		$this->validate();
		$xml = $this->getPaymentXML();
		return $this->sendPayment($xml);
	}

	/**
	* validate the data members to ensure that sufficient and valid information has been given
	*/
	private function validate() {
		$errmsg = '';

		if (strlen($this->accountID) === 0)
			$errmsg .= "accountID cannot be empty.\n";
		if (strlen($this->cardHoldersName) === 0)
			$errmsg .= "card holder's name cannot be empty.\n";
		if (strlen($this->cardNumber) === 0)
			$errmsg .= "card number cannot be empty.\n";

		// make sure that card expiry month is a number from 1 to 12
		if (gettype($this->cardExpiryMonth) != 'integer') {
			if (strlen($this->cardExpiryMonth) === 0)
				$errmsg .= "card expiry month cannot be empty.\n";
			else if (!is_numeric($this->cardExpiryMonth))
				$errmsg .= "card expiry month must be a number between 1 and 12.\n";
			else
				$this->cardExpiryMonth = intval($this->cardExpiryMonth);
		}
		if (gettype($this->cardExpiryMonth) == 'integer') {
			if ($this->cardExpiryMonth < 1 || $this->cardExpiryMonth > 12)
				$errmsg .= "card expiry month must be a number between 1 and 12.\n";
		}

		// make sure that card expiry year is a 2-digit or 4-digit year >= this year
		if (gettype($this->cardExpiryYear) != 'integer') {
			if (strlen($this->cardExpiryYear) === 0)
				$errmsg .= "card expiry year cannot be empty.\n";
			else if (!preg_match('/^\d\d(?:\d\d)?$/', $this->cardExpiryYear))
				$errmsg .= "card expiry year must be a two or four digit year.\n";
			else
				$this->cardExpiryYear = intval($this->cardExpiryYear);
		}
		if (gettype($this->cardExpiryYear) == 'integer') {
			$thisYear = intval(date_create()->format('Y'));
			if ($this->cardExpiryYear < 0 || $this->cardExpiryYear >= 100 && $this->cardExpiryYear < 2000 || $this->cardExpiryYear > $thisYear + 20)
				$errmsg .= "card expiry year must be a two or four digit year.\n";
			else {
				if ($this->cardExpiryYear > 100 && $this->cardExpiryYear < $thisYear)
					$errmsg .= "card expiry year can't be in the past.\n";
				else if ($this->cardExpiryYear < 100 && $this->cardExpiryYear < ($thisYear - 2000))
					$errmsg .= "card expiry year can't be in the past.\n";
			}
		}

		// ensure that amounts are numeric and positive, and that recurring amount > 0
		if (!is_numeric($this->amountInit) || $this->amountInit < 0)	// NB: initial amount can be 0
			$errmsg .= "initial amount must be given as a number in dollars and cents, or 0.\n";
		else if (!is_float($this->amountInit))
			$this->amountInit = (float) $this->amountInit;
		if (!is_numeric($this->amountRecur) || $this->amountRecur <= 0)
			$errmsg .= "recurring amount must be given as a number in dollars and cents.\n";
		else if (!is_float($this->amountRecur))
			$this->amountRecur = (float) $this->amountRecur;

		// ensure that interval is numeric and within range, and interval type is valid
		if (!is_numeric($this->intervalSize) || $this->intervalSize < 1 || $this->intervalSize > 31)
			$errmsg .= "interval must be numeric and between 1 and 31.\n";
		if (!is_numeric($this->intervalType) || !in_array(intval($this->intervalType), array(self::DAYS, self::WEEKS, self::MONTHS, self::YEARS)))
			$errmsg .= "interval type is invalid.\n";

		// ensure that dates are DateTime objects
		if (empty($this->dateInit))
			$this->dateInit = date_create();
		if (!(is_object($this->dateInit) && get_class($this->dateInit) == 'DateTime'))
			$errmsg .= "initial payment date must be a date.\n";
		if (!(is_object($this->dateStart) && get_class($this->dateStart) == 'DateTime'))
			$errmsg .= "recurring payment start date must be a date.\n";
		if (!(is_object($this->dateEnd) && get_class($this->dateEnd) == 'DateTime'))
			$errmsg .= "recurring payment end date must be a date.\n";

		if (strlen($errmsg) > 0) {
			throw new GFEwayException($errmsg);
		}
	}

	/**
	* create XML request document for payment parameters
	*
	* @return string
	*/
	public function getPaymentXML() {
		$xml = new XMLWriter();
		$xml->openMemory();
		$xml->startDocument('1.0', 'UTF-8');
		$xml->startElement('RebillUpload');
		$xml->startElement('NewRebill');
		$xml->writeElement('eWayCustomerID', $this->accountID);

		// customer data
		$xml->startElement('Customer');
		$xml->writeElement('CustomerRef', $this->customerReference);	// req?
		$xml->writeElement('CustomerTitle', $this->title);
		$xml->writeElement('CustomerFirstName', $this->firstName);		// req
		$xml->writeElement('CustomerLastName', $this->lastName);		// req
		$xml->writeElement('CustomerCompany', '');
		$xml->writeElement('CustomerJobDesc', '');
		$xml->writeElement('CustomerEmail', $this->emailAddress);
		$xml->writeElement('CustomerAddress', $this->address);
		$xml->writeElement('CustomerSuburb', $this->suburb);
		$xml->writeElement('CustomerState', $this->state);				// req
		$xml->writeElement('CustomerPostCode', $this->postcode);		// req
		$xml->writeElement('CustomerCountry', $this->country);			// req
		$xml->writeElement('CustomerPhone1', $this->phone);
		$xml->writeElement('CustomerPhone2', '');
		$xml->writeElement('CustomerFax', '');
		$xml->writeElement('CustomerURL', '');
		$xml->writeElement('CustomerComments', $this->customerComments);
		$xml->endElement();		// Customer

		// billing data
		$xml->startElement('RebillEvent');
		$xml->writeElement('RebillInvRef', $this->invoiceReference);
		$xml->writeElement('RebillInvDesc', $this->invoiceDescription);
		$xml->writeElement('RebillCCName', $this->cardHoldersName);
		$xml->writeElement('RebillCCNumber', $this->cardNumber);
		$xml->writeElement('RebillCCExpMonth', sprintf('%02d', $this->cardExpiryMonth));
		$xml->writeElement('RebillCCExpYear', sprintf('%02d', $this->cardExpiryYear % 100));
		$xml->writeElement('RebillInitAmt', number_format($this->amountInit * 100, 0, '', ''));
		$xml->writeElement('RebillInitDate', $this->dateInit->format('d/m/Y'));
		$xml->writeElement('RebillRecurAmt', number_format($this->amountRecur * 100, 0, '', ''));
		$xml->writeElement('RebillStartDate', $this->dateStart->format('d/m/Y'));
		$xml->writeElement('RebillInterval', $this->intervalSize);
		$xml->writeElement('RebillIntervalType', $this->intervalType);
		$xml->writeElement('RebillEndDate', $this->dateEnd->format('d/m/Y'));
		$xml->endElement();		// RebillEvent

		$xml->endElement();		// NewRebill
		$xml->endElement();		// RebillUpload

		return $xml->outputMemory();
	}

	/**
	* send the eWAY payment request and retrieve and parse the response
	*
	* @return GFEwayRecurringResponse
	* @param string $xml eWAY payment request as an XML document, per eWAY specifications
	*/
	private function sendPayment($xml) {
		// use sandbox if not from live website
		$url = $this->isLiveSite ? self::REALTIME_API_LIVE : self::REALTIME_API_SANDBOX;

		// execute the cURL request, and retrieve the response
		try {
			$responseXML = GFEwayPlugin::curlSendRequest($url, $xml, $this->sslVerifyPeer);
		}
		catch (GFEwayCurlException $e) {
			throw new GFEwayException("Error posting eWAY recurring payment to $url: " . $e->getMessage());
		}

		$response = new GFEwayRecurringResponse();
		$response->loadResponseXML($responseXML);
		return $response;
	}
}

/**
* Class for dealing with an eWAY recurring payment response
*/
class GFEwayRecurringResponse {
	/**
	* For a successful transaction "True" is passed and for a failed transaction "False" is passed.
	* @var boolean
	*/
	public $status;

	/**
	* the error severity, either Error or Warning
	* @var string max. 16 characters
	*/
	public $errorType;

	/**
	* the error response returned by the bank
	* @var string max. 255 characters
	*/
	public $error;

	/**
	* load eWAY response data as XML string
	*
	* @param string $response eWAY response as a string (hopefully of XML data)
	*/
	public function loadResponseXML($response) {
		try {
			// prevent XML injection attacks, and handle errors without warnings
			$oldDisableEntityLoader = libxml_disable_entity_loader(TRUE);
			$oldUseInternalErrors = libxml_use_internal_errors(TRUE);

//~ error_log(__METHOD__ . "\n" . $response);

			$xml = simplexml_load_string($response);
			if ($xml === false) {
				$errmsg = '';
				foreach (libxml_get_errors() as $error) {
					$errmsg .= $error->message;
				}
				throw new Exception($errmsg);
			}

			$this->status = (strcasecmp((string) $xml->Result, 'success') === 0);
			$this->errorType = (string) $xml->ErrorSeverity;
			$this->error = (string) $xml->ErrorDetails;

			// restore old libxml settings
			libxml_disable_entity_loader($oldDisableEntityLoader);
			libxml_use_internal_errors($oldUseInternalErrors);
		}
		catch (Exception $e) {
			// restore old libxml settings
			libxml_disable_entity_loader($oldDisableEntityLoader);
			libxml_use_internal_errors($oldUseInternalErrors);

			throw new GFEwayException('Error parsing eWAY recurring payments response: ' . $e->getMessage());
		}
	}
}
