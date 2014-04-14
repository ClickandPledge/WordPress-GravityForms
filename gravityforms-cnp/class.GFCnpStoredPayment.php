<?php
/**
* Classes for dealing with Click & Pledge stored payments
*
*/

/**
* Class for dealing with an Click & Pledge stored payment request
*/
class GFCnpStoredPayment {
	// environment / website specific members
	/**
	* NB: Stored Payments use the Direct Payments sandbox; there is no Stored Payments sandbox
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
	public $AccountID;

	/**
	* an invoice reference to track by (NB: see transactionNumber which is intended for invoice number or similar)
	* @var string max. 50 characters
	*/
	public $invoiceReference;

	/**
	* description of what is being purchased / paid for
	* @var string max. 10000 characters
	*/
	public $invoiceDescription;

	/**
	* total amount of payment, in dollars and cents as a floating-point number (will be converted to just cents for transmission)
	* @var float
	*/
	public $amount;

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
	* customer's address, including state, city and country
	* @var string max. 255 characters
	*/
	public $address;

	/**
	* customer's postcode
	* @var string max. 6 characters
	*/
	public $postcode;

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
	* CVN (Creditcard Verification Number) for verifying physical card is held by buyer
	* NB: this is ignored for Stored Payments!
	* @var string max. 3 or 4 characters (depends on type of card)
	*/
	public $cardVerificationNumber;

	/**
	* eWAYTrxnNumber - This value is returned to your website.
	*
	* You can pass a unique transaction number from your site. You can update and track the status of a transaction when eWAY
	* returns to your site.
	*
	* NB. This number is returned as 'ewayTrxnReference', member transactionReference of GFEwayStoredResponse.
	*
	* @var string max. 16 characters
	*/
	public $transactionNumber;

	/**
	* optional additional information for use in shopping carts, etc.
	* @var string max. 255 characters
	*/
	public $option1;

	/**
	* optional additional information for use in shopping carts, etc.
	* @var string max. 255 characters
	*/
	public $option2;

	/**
	* optional additional information for use in shopping carts, etc.
	* @var string max. 255 characters
	*/
	public $option3;

	/** host for the Click & Pledge Real Time API in the developer sandbox environment */
	const REALTIME_API_SANDBOX = 'https://paas.cloud.clickandpledge.com/paymentservice.svc?wsdl';
	/** host for the Click & Pledge Real Time API in the production environment */
	const REALTIME_API_LIVE = 'https://paas.cloud.clickandpledge.com/paymentservice.svc?wsdl';

	/**
	* populate members with defaults, and set account and environment information
	*
	* @param string $AccountID Click & Pledge account ID
	* @param boolean $isLiveSite running on the live (production) website
	*/
	public function __construct($AccountID, $isLiveSite = FALSE) {
		$this->sslVerifyPeer = TRUE;
		$this->isLiveSite = $isLiveSite;
		$this->AccountID = $AccountID;
	}

	/**
	* process a payment against Click & Pledge; throws exception on error with error described in exception message.
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

		if (strlen($this->AccountID) === 0)
			$errmsg .= "AccountID cannot be empty.\n";
		if (!is_numeric($this->amount) || $this->amount <= 0)
			$errmsg .= "amount must be given as a number in dollars and cents.\n";
		else if (!is_float($this->amount))
			$this->amount = (float) $this->amount;
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
			else if (!preg_match('/^\d\d(\d\d)?$/', $this->cardExpiryYear))
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

		if (strlen($errmsg) > 0)
			throw new GFCnpException($errmsg);
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
		$xml->startElement('ewaygateway');

		$xml->writeElement('ewayCustomerID', $this->AccountID);
		$xml->writeElement('ewayTotalAmount', number_format($this->amount * 100, 0, '', ''));
		$xml->writeElement('ewayCustomerFirstName', $this->firstName);
		$xml->writeElement('ewayCustomerLastName', $this->lastName);
		$xml->writeElement('ewayCustomerEmail', $this->emailAddress);
		$xml->writeElement('ewayCustomerAddress', $this->address);
		$xml->writeElement('ewayCustomerPostcode', $this->postcode);
		$xml->writeElement('ewayCustomerInvoiceDescription', $this->invoiceDescription);
		$xml->writeElement('ewayCustomerInvoiceRef', $this->invoiceReference);
		$xml->writeElement('ewayCardHoldersName', $this->cardHoldersName);
		$xml->writeElement('ewayCardNumber', $this->cardNumber);
		$xml->writeElement('ewayCardExpiryMonth', sprintf('%02d', $this->cardExpiryMonth));
		$xml->writeElement('ewayCardExpiryYear', sprintf('%02d', $this->cardExpiryYear % 100));
		$xml->writeElement('ewayTrxnNumber', $this->transactionNumber);
		//~ $xml->writeElement('ewayCVN', $this->cardVerificationNumber);	// NB: must not be present for Stored Payments!
		$xml->writeElement('ewayOption1', $this->option1);
		$xml->writeElement('ewayOption2', $this->option2);
		$xml->writeElement('ewayOption3', $this->option3);

		$xml->endElement();		// ewaygateway

		return $xml->outputMemory();
	}

	/**
	* send the Click & Pledge payment request and retrieve and parse the response
	* @return GFEwayStoredResponse
	* @param string $xml Click & Pledge payment request as an XML document, per Click & Pledge specifications
	*/
	private function sendPayment($xml) {
		// use sandbox if not from live website
		$url = $this->isLiveSite ? self::REALTIME_API_LIVE : self::REALTIME_API_SANDBOX;

		// execute the cURL request, and retrieve the response
		try {
			$responseXML = GFCnpPlugin::curlSendRequest($url, $xml, $this->sslVerifyPeer);
		}
		catch (GFEwayCurlException $e) {
			throw new GFEwayException("Error posting Click & Pledge payment to $url: " . $e->getMessage());
		}

		$response = new GFCnpStoredResponse();
		$response->loadResponseXML($responseXML);
		return $response;
	}
}

/**
* Class for dealing with an Click & Pledge stored payment response
*/
class GFCnpStoredResponse {
	/**
	* For a successful transaction "True" is passed and for a failed transaction "False" is passed.
	* @var boolean
	*/
	public $status;

	/**
	* eWAYTrxnNumber
	* @var string max. 16 characters
	*/
	public $transactionNumber;

	/**
	* eWAYTrxnNumber referenced in transaction (e.g. invoice number)
	* @var string max. 16 characters
	*/
	public $transactionReference;

	/**
	* optional additional information for use in shopping carts, etc.
	* @var string max. 255 characters
	*/
	public $option1;

	/**
	* optional additional information for use in shopping carts, etc.
	* @var string max. 255 characters
	*/
	public $option2;

	/**
	* optional additional information for use in shopping carts, etc.
	* @var string max. 255 characters
	*/
	public $option3;

	/**
	* If the transaction is successful, this is the bank authorisation number. This is also sent in the email receipt.
	* @var string max. 6 characters
	*/
	public $authCode;

	/**
	* total amount of payment as processed, in dollars and cents as a floating-point number
	* @var float
	*/
	public $amount;

	/**
	* the response returned by the bank, and can be related to both successful and failed transactions.
	* @var string max. 100 characters
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

			$this->status = (strcasecmp((string) $xml->ewayTrxnStatus, 'true') === 0);
			$this->transactionNumber = (string) $xml->ewayTrxnNumber;
			$this->transactionReference = (string) $xml->ewayTrxnReference;
			$this->option1 = (string) $xml->ewayTrxnOption1;
			$this->option2 = (string) $xml->ewayTrxnOption2;
			$this->option3 = (string) $xml->ewayTrxnOption3;
			$this->authCode = (string) $xml->ewayAuthCode;
			$this->error = (string) $xml->ewayTrxnError;

			// if we got an amount, convert it back into dollars.cents from just cents
			if (!empty($xml->ewayReturnAmount))
				$this->amount = floatval($xml->ewayReturnAmount) / 100.0;
			else
				$this->amount = NULL;

			// restore old libxml settings
			libxml_disable_entity_loader($oldDisableEntityLoader);
			libxml_use_internal_errors($oldUseInternalErrors);
		}
		catch (Exception $e) {
			// restore old libxml settings
			libxml_disable_entity_loader($oldDisableEntityLoader);
			libxml_use_internal_errors($oldUseInternalErrors);

			throw new GFCnpException('Error parsing Click & Pledge response: ' . $e->getMessage());
		}
	}
}
