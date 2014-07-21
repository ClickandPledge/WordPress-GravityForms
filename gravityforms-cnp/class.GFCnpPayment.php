<?php
/**
* Class for dealing with an Click & Pledge payment
*/
class GFCnpPayment {
	// environment / website specific members
	/**
	* default FALSE, use Click & Pledge sandbox unless set to TRUE
	* @var boolean
	*/
	public $isLiveSite;
	
	/**
	Specify the order mode.  Test mode may be used for testing and development.  In test mode only test credit card numbers may be used.  In Production mode transactions are only performed with real credit cards.
	allowed values 'Test' and 'Production'
	*/
	public $mode;

	/**
	* default TRUE, whether to validate the remote SSL certificate
	* @var boolean
	*/
	public $sslVerifyPeer;

	// payment specific members
	/**
	* Trio account number provide by Click & Pledge
	* @var string min. 1 and max. 9999999999 characters
	*/
	public $AccountID;

	/**
	* A unique identifier provided by Click & Pledge
	* @var string min. 36 and max. 36 characters
	*/
	public $AccountGuid;
	
	/**
	* Global options at Click & Pledge
	*/
	public $Options_new;
	
	/**
	* total amount of payment
	* @var float
	*/
	public $amount;


	/**
	* customer's postcode
	* @var string min 2 and max. 20 characters
	*/
	public $postcode;

	/**
	* name on credit card
	* @var string min. 2 and max. 50 characters
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
	* @var string max. 3 or 4 characters (depends on type of card)
	*/
	public $cardVerificationNumber;


	/**
	* Complete form data which is submitting by user
	* @var string 2 characters
	*/
	public $formData;



	/**
	* populate members with defaults, and set account and environment information
	*
	* @param string $AccountID Click & Pledge account ID
	* @param boolean $isLiveSite running on the live (production) website
	*/
	public function __construct($Options_new, $isLiveSite = FALSE, $formData) {
		$this->sslVerifyPeer = TRUE;
		$this->isLiveSite = $isLiveSite;
		$this->AccountID = $Options_new['AccountID'];
		$this->AccountGuid = $Options_new['AccountGuid'];
		$this->mode = ($Options_new['useTest'] == 'Y') ? 'Test' : 'Production';
		$this->formData = $formData;
		$this->Options_new = $Options_new;
		}
		


	/**
	* process a payment against Click & Pledge; throws exception on error with error described in exception message.
	*/
	public function processPayment() {
		$this->validate($this->Options_new, $this->formData);
		
		$xml = $this->getPaymentXML($this->Options_new, $this->formData);
		//echo $xml;
		//die('CNP Payment');
		return $this->sendPayment($xml);
	}

	/**
	* validate the data members to ensure that sufficient and valid information has been given
	*/
	private function validate($options, $formData) {
		$errmsg = '';
		$adminerrors = false;
		
		$form_currency = GFCommon::get_currency();
		if(!in_array($form_currency, array('USD', 'EUR', 'CAD', 'GBP')))
		{
			$errmsg .= "We are supporting 'USD', 'EUR', 'CAD', 'GBP'. Check your API credentials and make sure your currency is supported.\n";
			$adminerrors = true;
		}
		if (strlen($this->AccountID) === 0) {
			$errmsg .= "AccountID cannot be empty. Please contact administrator.\n";
			$adminerrors = true;
		}			
		if (strlen($options['AccountGuid']) === 0) {
			$errmsg .= "GUID cannot be empty. Please contact administrator\n";
			$adminerrors = true;
		}
		if($formData->creditcardCount != 0) {
			if ($formData->firstName == '' || $formData->lastName == '') {
				if($formData->firstName == '')
				$errmsg .= "Form should contain First Name field and you should enter First Name. Please contact administrator.\n";
				if($formData->lastName == '')
				$errmsg .= "Form should contain Last Name field and you should enter Last Name. Please contact administrator.\n";
				$adminerrors = true;
			}
		}
		
		if(isset($formData->shippingfields) && count($formData->shippingfields) && $formData->address_street == '') {
			$errmsg .= "Form contains shipping fields but do not have shipping address. Please contact administrator.\n";
			$adminerrors = true;
		}
		
		if(!$adminerrors) {
		if ($formData->firstName == '')
			$errmsg .= "You should enter First Name to process your payment.\n";
		if (strlen($formData->firstName) > 50)
			$errmsg .= "First Name should not exceed 50 characters length.\n";
		/*if ($formData->lastName == '')
			$errmsg .= "You should enter Last Name to process your payment.\n";*/
		if (strlen($formData->lastName) > 50)
			$errmsg .= "Last Name should not exceed 50 characters length.\n";
		if (strlen($formData->productdetails) === 0)
			$errmsg .= "Cart should have at least one item.\n";
		
		
		if(isset($formData->shippingfields) && count($formData->shippingfields)) {
			if( $orderplaced->address_street != '' )
			$errmsg .= "Please enter shipping address.\n";
			if( $orderplaced->address_suburb != '' )
			$errmsg .= "Please enter shipping city.\n";
			if( $orderplaced->address_state != '' )
			$errmsg .= "Please enter shipping state.\n";
			if( $orderplaced->postcode != '' )
			$errmsg .= "Please enter shipping postal code.\n";
			if( $orderplaced->address_country != '' )
			$errmsg .= "Please seelct country.\n";
		}
		if(count($formData->needtovalidatefields)) {
			for($r = 0; $r < count($formData->needtovalidatefields); $r++)
			{
				if($formData->needtovalidatefields[$r]['type'] == 'price' && !preg_match('/^\d+(?:\.\d{2})?$/', $formData->needtovalidatefields[$r]['value']))
				{
					$errmsg .= "Invalid price.\n";
				}
			}
		}
		
		if (!is_numeric($this->amount) || $this->amount < 0)
			$errmsg .= "amount must be given as a number.\n";
		else if (!is_float($this->amount))
			$this->amount = (float) $this->amount;
		if((isset($formData->recurring)) && ($formData->recurring['isRecurring'] == 'yes') && $this->amount == 0) {
			$errmsg .= "amount must be greater than zero for recurring transaction.\n";
		}
		if($formData->creditcardCount != 0) {
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

			if (!is_numeric($formData->ccCVN)) {
			$errmsg .= "Security Code should be digits only.\n";
			}
			if (strlen($formData->ccCVN) > 4)
				$errmsg .= "CVV should be 3 or 4 digits only.\n";
			if (strlen($formData->ccName) == 1)
				$errmsg .= "Cardholder Name should be 2 to 50 characters length.\n";
			if (strlen($formData->ccName) > 50)
				$errmsg .= "Cardholder Name should not exceed 50 characters length.\n";
		} else {
			if (strlen($formData->ecRouting) > 9)
				$errmsg .= "Routing Number should be max 9 digits only.\n";
		}
		}
		
		if (strlen($errmsg) > 0) {
			throw new GFCnpException($errmsg);
		}
	}

	/**
	     * Get user's IP address
	     */
	function get_user_ip() {
		$ipaddress = '';
		 if ($_SERVER['HTTP_CLIENT_IP'])
			 $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		 else if($_SERVER['HTTP_X_FORWARDED_FOR'])
			 $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		 else if($_SERVER['HTTP_X_FORWARDED'])
			 $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		 else if($_SERVER['HTTP_FORWARDED_FOR'])
			 $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		 else if($_SERVER['HTTP_FORWARDED'])
			 $ipaddress = $_SERVER['HTTP_FORWARDED'];
		 else
			 $ipaddress = $_SERVER['REMOTE_ADDR'];

		 return $ipaddress; 
	}
	
	function safeString( $str,  $length=1, $start=0 )
	{
		return substr( htmlspecialchars( ( $str ) ), $start, $length );
	}
	
	/**
	* create XML request document for payment parameters
	*
	* @return string
	*/
	public function getPaymentXML($configValues, $orderplaced) 
	{	
		$dom = new DOMDocument('1.0', 'UTF-8');
		$root = $dom->createElement('CnPAPI', '');
		$root->setAttribute("xmlns","urn:APISchema.xsd");
		$root = $dom->appendChild($root);

		$version=$dom->createElement("Version","1.5");
		$version=$root->appendChild($version);

		$engine = $dom->createElement('Engine', '');
		$engine = $root->appendChild($engine);

		$application = $dom->createElement('Application','');
		$application = $engine->appendChild($application);

		$applicationid=$dom->createElement('ID','CnP_PaaS_FM_GravityForm'); //
		$applicationid=$application->appendChild($applicationid);

		$applicationname=$dom->createElement('Name','CnP_PaaS_FM_GravityForm'); 
		$applicationid=$application->appendChild($applicationname);

		$applicationversion=$dom->createElement('Version','2.000.004.000.20140721');  //2.000.000.000.20130103 Version-Minor change-Bug Fix-Internal Release Number -Release Date
		$applicationversion=$application->appendChild($applicationversion);

		$request = $dom->createElement('Request', '');
		$request = $engine->appendChild($request);

		$operation=$dom->createElement('Operation','');
		$operation=$request->appendChild( $operation );

		$operationtype=$dom->createElement('OperationType','Transaction');
		$operationtype=$operation->appendChild($operationtype);
		
		$ipaddress=$dom->createElement('IPAddress',$this->get_user_ip());
		$ipaddress=$operation->appendChild($ipaddress);
		
		$httpreferrer=$dom->createElement('UrlReferrer',$_SERVER['HTTP_REFERER']);
		$httpreferrer=$operation->appendChild($httpreferrer);
		
		$authentication=$dom->createElement('Authentication','');
		$authentication=$request->appendChild($authentication);

		$accounttype=$dom->createElement('AccountGuid',$configValues['AccountGuid'] ); 
		$accounttype=$authentication->appendChild($accounttype);
		
		$accountid=$dom->createElement('AccountID',$configValues['AccountID'] );
		$accountid=$authentication->appendChild($accountid);
				 
		$order=$dom->createElement('Order','');
		$order=$request->appendChild($order);

		$ordermode=$dom->createElement('OrderMode',$this->mode);
		$ordermode=$order->appendChild($ordermode);				
										
		$cardholder=$dom->createElement('CardHolder','');
		$cardholder=$order->appendChild($cardholder);
		
		if($orderplaced->firstName || $orderplaced->ccName) {
		$billinginfo=$dom->createElement('BillingInformation','');
		$billinginfo=$cardholder->appendChild($billinginfo);
		
		if($orderplaced->firstName) {
		$billfirst_name=$dom->createElement('BillingFirstName',$this->safeString($orderplaced->firstName,50));
		$billfirst_name=$billinginfo->appendChild($billfirst_name);
		}
		
		if($orderplaced->lastName) {		
		$billlast_name=$dom->createElement('BillingLastName',$this->safeString($orderplaced->lastName,50));
		$billlast_name=$billinginfo->appendChild($billlast_name);
		}

		if (isset($orderplaced->email) && $orderplaced->email != '')
		{
			$bill_email=$dom->createElement('BillingEmail',$orderplaced->email);
			$bill_email=$billinginfo->appendChild($bill_email);
		}
		else
		{
			$current_user = wp_get_current_user();
			if($current_user->ID)
			{
			$bill_email=$dom->createElement('BillingEmail',$current_user->data->user_email);
			$bill_email=$billinginfo->appendChild($bill_email);
			}
		}
		
		if( $orderplaced->phone != '' )
		{
			$bill_phone=$dom->createElement('BillingPhone',$this->safeString($orderplaced->phone, 50));
			$bill_phone=$billinginfo->appendChild($bill_phone);
		}
		} //Billing Information
		
		if( $orderplaced->address != '' ) {		
		$billingaddress=$dom->createElement('BillingAddress','');
		$billingaddress=$cardholder->appendChild($billingaddress);
		
		if( $orderplaced->address_street != '' ) {
		$billingaddress1=$dom->createElement('BillingAddress1',$this->safeString($orderplaced->address_street,100));
		$billingaddress1=$billingaddress->appendChild($billingaddress1);
		}
				
		if(!empty($orderplaced->address_suburb)) {
		$billing_city=$dom->createElement('BillingCity',$this->safeString($orderplaced->address_suburb,50));
		$billing_city=$billingaddress->appendChild($billing_city);
		}

		if(!empty($orderplaced->address_state)) {
		$billing_state=$dom->createElement('BillingStateProvince',$this->safeString($orderplaced->address_state,50));
		$billing_state=$billingaddress->appendChild($billing_state);
		}
		
		if(!empty($orderplaced->postcode)) {		
		$billing_zip=$dom->createElement('BillingPostalCode',$this->safeString( $orderplaced->postcode,20 ));
		$billing_zip=$billingaddress->appendChild($billing_zip);
		}

		if(!empty($orderplaced->address_country)) {
		$countries = simplexml_load_file( WP_PLUGIN_URL.DIRECTORY_SEPARATOR.plugin_basename( dirname(__FILE__)).DIRECTORY_SEPARATOR.'Countries.xml' );
		$billing_country_id = '';
		foreach( $countries as $country ){
			if( $country->attributes()->Name == $orderplaced->address_country ){
				$billing_country_id = $country->attributes()->Code;
			} 
		}
		if($billing_country_id) {
		$billing_country=$dom->createElement('BillingCountryCode',str_pad($billing_country_id, 3, "0", STR_PAD_LEFT));
		$billing_country=$billingaddress->appendChild($billing_country);
		}
		}
		} //Billing Address
		
		if(isset($orderplaced->shippingfields) && count($orderplaced->shippingfields)) {
			$shippinginfo=$dom->createElement('ShippingInformation','');
			$shippinginfo=$cardholder->appendChild($shippinginfo);
			
			$shippingaddress=$dom->createElement('ShippingAddress','');
			$shippingaddress=$shippinginfo->appendChild($shippingaddress);
			
			if( $orderplaced->address_street_shipping != '' )
			{
				$parts = explode(',', $orderplaced->address_street_shipping);
				$ship_address1=$dom->createElement('ShippingAddress1',$this->safeString($parts[0],60));
				$ship_address1=$shippingaddress->appendChild($ship_address1);
				
				if(count($parts) > 1) {
				$ship_address2=$dom->createElement('ShippingAddress2',$this->safeString($parts[1],60));
				$ship_address2=$shippingaddress->appendChild($ship_address2);
				}
			} else {
				$parts = explode(',', $orderplaced->address_street);
				$ship_address1=$dom->createElement('ShippingAddress1',$this->safeString($parts[0],60));
				$ship_address1=$shippingaddress->appendChild($ship_address1);
				
				if(count($parts) > 1) {
				$ship_address2=$dom->createElement('ShippingAddress2',$this->safeString($parts[1],60));
				$ship_address2=$shippingaddress->appendChild($ship_address2);
				}
			}

			if( $orderplaced->address_suburb_shipping != '' )
			{
				$ship_city=$dom->createElement('ShippingCity',$this->safeString($orderplaced->address_suburb_shipping, 40));
				$ship_city=$shippingaddress->appendChild($ship_city);
			}
			else
			{
				$ship_city=$dom->createElement('ShippingCity',$this->safeString($orderplaced->address_suburb, 40));
				$ship_city=$shippingaddress->appendChild($ship_city);
			}

			if( $orderplaced->address_state_shipping != '' )
			{
				$ship_state=$dom->createElement('ShippingStateProvince',$this->safeString($orderplaced->address_state_shipping, 40));
				$ship_state=$shippingaddress->appendChild($ship_state);
			}
			else
			{
				$ship_state=$dom->createElement('ShippingStateProvince',$this->safeString($orderplaced->address_state, 40));
				$ship_state=$shippingaddress->appendChild($ship_state);
			}
			
			if( $orderplaced->postcode_shipping != '' )
			{
				$ship_zip=$dom->createElement('ShippingPostalCode',$this->safeString($orderplaced->postcode_shipping, 20));
				$ship_zip=$shippingaddress->appendChild($ship_zip);
			}
			else
			{
				$ship_zip=$dom->createElement('ShippingPostalCode',$this->safeString($orderplaced->postcode, 20));
				$ship_zip=$shippingaddress->appendChild($ship_zip);
			}
			
			if( $orderplaced->address_country_shipping != '' )
			{
				$countries = simplexml_load_file( WP_PLUGIN_URL.DIRECTORY_SEPARATOR.plugin_basename( dirname(__FILE__)).DIRECTORY_SEPARATOR.'Countries.xml' );
				
				$shipping_country_id = '';
				foreach( $countries as $country ){
					if( $country->attributes()->Name == $orderplaced->address_country_shipping ){
						$shipping_country_id = $country->attributes()->Code;
					} 
				}
				if($shipping_country_id) {
				$ship_country=$dom->createElement('ShippingCountryCode',$shipping_country_id);
				$ship_country=$shippingaddress->appendChild($ship_country);
				}
			}
			else
			{
				$countries = simplexml_load_file( WP_PLUGIN_URL.DIRECTORY_SEPARATOR.plugin_basename( dirname(__FILE__)).DIRECTORY_SEPARATOR.'Countries.xml' );
				
				$shipping_country_id = '';
				foreach( $countries as $country ){
					if( $country->attributes()->Name == $orderplaced->address_country ){
						$shipping_country_id = $country->attributes()->Code;
					} 
				}
				if($shipping_country_id) {
				$ship_country=$dom->createElement('ShippingCountryCode',$shipping_country_id);
				$ship_country=$shippingaddress->appendChild($ship_country);
				}
			}
		}
		if(isset($orderplaced->customfields) && count($orderplaced->customfields))
		{
			$customfieldlist = $dom->createElement('CustomFieldList','');
			$customfieldlist = $cardholder->appendChild($customfieldlist);
			$custonodes = 0;
			for ($p = 0; $p < count($orderplaced->customfields); $p++) 
			{
			if((substr($orderplaced->customfields[$p]['FieldName'], 0, 5) != '{SKU}') && $orderplaced->customfields[$p]['FieldValue'] != '') {
				$custonodes++;
				$customfield = $dom->createElement('CustomField','');
				$customfield = $customfieldlist->appendChild($customfield);
					
				$fieldname = $dom->createElement('FieldName',$orderplaced->customfields[$p]['FieldName']);
				$fieldname = $customfield->appendChild($fieldname);
					
				$fieldvalue = $dom->createElement('FieldValue',$this->safeString($orderplaced->customfields[$p]['FieldValue'], 500));
				$fieldvalue = $customfield->appendChild($fieldvalue);
			}
			}
			if($custonodes == 0)
			$cardholder->removeChild($customfieldlist);
		}
		
		$paymentmethod=$dom->createElement('PaymentMethod','');
		$paymentmethod=$cardholder->appendChild($paymentmethod);
		if($orderplaced->creditcardCount != 0) 
		{
			$payment_type=$dom->createElement('PaymentType','CreditCard');
			$payment_type=$paymentmethod->appendChild($payment_type);
			
		 	$creditcard=$dom->createElement('CreditCard','');
			$creditcard=$paymentmethod->appendChild($creditcard);
			
			$credit_card_name = $orderplaced->ccName;						
			$credit_name=$dom->createElement('NameOnCard',$this->safeString( $credit_card_name, 50));
			$credit_name=$creditcard->appendChild($credit_name);
					
			$credit_number=$dom->createElement('CardNumber',$this->safeString( str_replace(' ', '', $orderplaced->ccNumber), 17));
			$credit_number=$creditcard->appendChild($credit_number);

			$credit_cvv=$dom->createElement('Cvv2',$orderplaced->ccCVN);
			$credit_cvv=$creditcard->appendChild($credit_cvv);

			$credit_expdate=$dom->createElement('ExpirationDate',str_pad($orderplaced->ccExpMonth,2,'0',STR_PAD_LEFT) ."/" .substr($orderplaced->ccExpYear,2,2));
			$credit_expdate=$creditcard->appendChild($credit_expdate);
		} 
		else 
		{
			
			$payment_type=$dom->createElement('PaymentType','Check');
			$payment_type=$paymentmethod->appendChild($payment_type);
			
			$echeck=$dom->createElement('Check','');
			$echeck=$paymentmethod->appendChild($echeck);
			
			$ecAccount=$dom->createElement('AccountNumber',$this->safeString( $orderplaced->ecAccount, 17));
			$ecAccount=$echeck->appendChild($ecAccount);
			
			$ecAccount_type=$dom->createElement('AccountType',$orderplaced->ecAccount_type);
			$ecAccount_type=$echeck->appendChild($ecAccount_type);
			
			$ecRouting=$dom->createElement('RoutingNumber',$this->safeString( $orderplaced->ecRouting, 9));
			$ecRouting=$echeck->appendChild($ecRouting);
			
			$ecCheck=$dom->createElement('CheckNumber',$this->safeString( $orderplaced->ecCheck, 10));
			$ecCheck=$echeck->appendChild($ecCheck);
			
			$ecChecktype=$dom->createElement('CheckType',$orderplaced->ecChecktype);
			$ecChecktype=$echeck->appendChild($ecChecktype);
			
			$ecName=$dom->createElement('NameOnAccount',$this->safeString( $orderplaced->ecName, 100));
			$ecName=$echeck->appendChild($ecName);
			
			$ecIdtype=$dom->createElement('IdType',$orderplaced->ecIdtype);
			$ecIdtype=$echeck->appendChild($ecIdtype);
			/*
			$IdNumber=$dom->createElement('IdNumber',$this->safeString( $orderplaced->ecIdNumber, 30));
			$IdNumber=$creditcard->appendChild($IdNumber);
			
			$IdStateCode=$dom->createElement('IdStateCode', $orderplaced->ecIdStateCode);
			$IdStateCode=$creditcard->appendChild($IdStateCode);
			*/
		}
		
		$total_calculate = 0;
		
		//Products processing
		if(isset($orderplaced->productdetails) && count($orderplaced->productdetails))
		{
			$orderitemlist=$dom->createElement('OrderItemList','');
			$orderitemlist=$order->appendChild($orderitemlist);		
			$p = 0;	
			foreach ( $orderplaced->productdetails as  $pr) 
			{
				//if($pr['productField'] == '' ) 
				{
					$OptionValue = '';
					$orderitem=$dom->createElement('OrderItem','');
					$orderitem=$orderitemlist->appendChild($orderitem);

					$itemid=$dom->createElement('ItemID',($p+1));
					$itemid=$orderitem->appendChild($itemid);				
					$tempName = $pr['ItemName'];
					$tempName2 = '';
					$cost = $pr['UnitPrice'];
					foreach($orderplaced->productdetails as $sub)
					{
						if($sub['productField'] == $pr['ItemID']) {
							$tempName2 .= $sub['ItemName'];
							if($sub['OptionValue']) {
							$tempName2 .= ':'.$sub['OptionValue'];
							$OptionValue = $sub['OptionValue'];
							}
							$cost += $sub['UnitPrice'];
						} elseif($sub['OptionValue'] != '' && $pr['ItemID'] == $sub['ItemID']) {
							$OptionValue = $sub['OptionValue'];
							$tempName2 .= $sub['OptionValue'];
						}
					}
					
					$tempName = ($tempName2) ? $tempName . ' ('.$tempName2.')' : $tempName;
					$itemname=$dom->createElement('ItemName',$this->safeString(trim($tempName), 50));
					$itemname=$orderitem->appendChild($itemname);

					$quntity=$dom->createElement('Quantity',$pr['Quantity']);
					$quntity=$orderitem->appendChild($quntity);

					if((isset($orderplaced->recurring)) && ($orderplaced->recurring['isRecurring'] == 'yes')) {
						if($orderplaced->recurring['indefinite'] == 'yes') {
							$Installments = 999;
						} elseif($orderplaced->recurring['Installments']) {
							$Installments = $orderplaced->recurring['Installments'];
						}
						else {
							$Installments = 999;
						}
						
						if($orderplaced->recurring['RecurringMethod'] == 'Installment') {
						$total_calculate += number_format(($cost/$Installments),2,'.','')*$pr['Quantity'];
						$unitprice=$dom->createElement('UnitPrice',(number_format(($cost/$Installments),2,'.','')*100));
						$unitprice=$orderitem->appendChild($unitprice);
						} else {
						$total_calculate += $cost*$pr['Quantity'];
						$unitprice=$dom->createElement('UnitPrice',($cost*100));
						$unitprice=$orderitem->appendChild($unitprice);
						}
					} else {
					$total_calculate += $cost*$pr['Quantity'];
					$unitprice=$dom->createElement('UnitPrice',($cost*100));
					$unitprice=$orderitem->appendChild($unitprice);
					}
					
					//SKU Handling
					foreach($orderplaced->customfields as $sub)
					{
						if((substr($sub['FieldName'], 0, 5) == '{SKU}') && $sub['FieldValue'] != '') {
							$parts = explode('}{OPTION=', $sub['FieldName']);
							//Format:{SKU}{FIELDID=11}{OPTION=N}
							//print_r($parts);
							if(count($parts) > 1) //TO handle if product has options
							{
							$id = $parts[0];
							$id = substr($id, 14);
							$val = substr($parts[1], 0, -1);
							}
							else
							{
							$id = substr($parts[0], 14);
							$id = substr($id, 0, -1);
							$val = '';
							}

							if($id == $pr['ItemID'] && $OptionValue == substr($parts[1],0,-1)) 
							{
								
								if(count($parts) > 1) //TO handle if product has options
								{
									if($OptionValue != '' && $val != '' && $OptionValue == substr($parts[1],0,-1))
									{
										$sku_code=$dom->createElement('SKU',$this->safeString($sub['FieldValue'], 100));
										$sku_code=$orderitem->appendChild($sku_code);
									}
								} else {
								$sku_code=$dom->createElement('SKU',$this->safeString($sub['FieldValue'], 100));
								$sku_code=$orderitem->appendChild($sku_code);
								}
							}
							
						}
					}
					
				}
			}
			
		}
		//echo '<pre>';
		//print_r($orderplaced);
		//die('ffffffffff');
		$ShippingValue = 0;
		if(isset($orderplaced->shippingfields) && count($orderplaced->shippingfields)) {
			$shipping=$dom->createElement('Shipping','');
			$shipping=$order->appendChild($shipping);
			foreach ( $orderplaced->shippingfields as  $sp) 
			{
				$ShippingValue_Local = 0;
				$shipping_method=$dom->createElement('ShippingMethod',$sp['ShippingMethod']);
				$shipping_method=$shipping->appendChild($shipping_method);
				$ShippingValue_Local = $sp['ShippingValue'];
				if((isset($orderplaced->recurring)) && ($orderplaced->recurring['isRecurring'] == 'yes')) {
					if($orderplaced->recurring['indefinite'] == 'yes') {
						$Installments = 999;
					} elseif($orderplaced->recurring['Installments']) {
						$Installments = $orderplaced->recurring['Installments'];
					}
					else {
						$Installments = 999;
					}
					if($orderplaced->recurring['RecurringMethod'] == 'Installment') {
					$ShippingValue_Local = $ShippingValue_Local/$Installments;
					} else {
					$ShippingValue_Local = $ShippingValue_Local;
					}
				}
				$ShippingValue += number_format($ShippingValue_Local, 2, '.', '');
				$shipping_value = $dom->createElement('ShippingValue', number_format($ShippingValue_Local, 2, '.', '')*100);
				$shipping_value=$shipping->appendChild($shipping_value);				
			}
		}
		
		$receipt=$dom->createElement('Receipt','');
		$receipt=$order->appendChild($receipt);

		$recipt_lang=$dom->createElement('Language','ENG');
		$recipt_lang=$receipt->appendChild($recipt_lang);
		
		if( $configValues['OrganizationInformation'] != '')
		{
			$recipt_org=$dom->createElement('OrganizationInformation',$this->safeString($configValues['OrganizationInformation'], 1500));
			$recipt_org=$receipt->appendChild($recipt_org);
		}
		
		if( $configValues['ThankYouMessage'] != '')
		{
			$recipt_thanks=$dom->createElement('ThankYouMessage',$this->safeString($configValues['ThankYouMessage'], 500));
			$recipt_thanks=$receipt->appendChild($recipt_thanks);
		}
		
		if( $configValues['TermsCondition'] != '')
		{
			$recipt_terms=$dom->createElement('TermsCondition',$this->safeString($configValues['TermsCondition'], 1500));
			$recipt_terms=$receipt->appendChild($recipt_terms);
		}
		
		if($configValues['email_customer'] == 'yes') 
		{
			if (isset($orderplaced->email) && $orderplaced->email != '') 
			{
			$recipt_email=$dom->createElement('EmailNotificationList','');
			$recipt_email=$receipt->appendChild($recipt_email);			
										
			$email_note=$dom->createElement('NotificationEmail',$orderplaced->email);
			$email_note=$recipt_email->appendChild($email_note);
			}
			else
			{
			$current_user = wp_get_current_user();
			if($current_user->ID)
			{
			$recipt_email=$dom->createElement('EmailNotificationList','');
			$recipt_email=$receipt->appendChild($recipt_email);			
										
			$email_note=$dom->createElement('NotificationEmail',$current_user->data->user_email);
			$email_note=$recipt_email->appendChild($email_note);
			}
			}//EmailNotificationList
		}
		
		$transation=$dom->createElement('Transaction','');
		$transation=$order->appendChild($transation);

		$trans_type=$dom->createElement('TransactionType','Payment');
		$trans_type=$transation->appendChild($trans_type);

		$trans_desc=$dom->createElement('DynamicDescriptor','DynamicDescriptor');
		$trans_desc=$transation->appendChild($trans_desc); 
		
		if((isset($orderplaced->recurring)) && ($orderplaced->recurring['isRecurring'] == 'yes')) 
		{
			$trans_recurr=$dom->createElement('Recurring','');
			$trans_recurr=$transation->appendChild($trans_recurr);
			if($orderplaced->recurring['indefinite'] == 'yes') {
				$total_installment=$dom->createElement('Installment',999);
				$total_installment=$trans_recurr->appendChild($total_installment);
			}
			elseif  ( $orderplaced->recurring['Installments'] )
			{
				$total_installment=$dom->createElement('Installment',$orderplaced->recurring['Installments']);
				$total_installment=$trans_recurr->appendChild($total_installment);
			}
			else
			{
				$total_installment=$dom->createElement('Installment',999);
				$total_installment=$trans_recurr->appendChild($total_installment);
			}			
			$total_periodicity=$dom->createElement('Periodicity',$orderplaced->recurring['Periodicity']);
			$total_periodicity=$trans_recurr->appendChild($total_periodicity);
			
			if( $orderplaced->recurring['RecurringMethod'] != '' ) {
				$RecurringMethod=$dom->createElement('RecurringMethod',$orderplaced->recurring['RecurringMethod']);
				$RecurringMethod=$trans_recurr->appendChild($RecurringMethod);
			} else {
				$RecurringMethod=$dom->createElement('RecurringMethod','Subscription');
				$RecurringMethod=$trans_recurr->appendChild($RecurringMethod);
			}	
		}
		
		$trans_totals=$dom->createElement('CurrentTotals','');
		$trans_totals=$transation->appendChild($trans_totals);
		
		if(isset($orderplaced->shippingfields) && count($orderplaced->shippingfields)) {
			$total_ship=$dom->createElement('TotalShipping',$ShippingValue*100);
			$total_ship=$trans_totals->appendChild($total_ship);
		}
		
		if(isset($orderplaced->recurring) && $orderplaced->recurring['isRecurring'] == 'yes' && $orderplaced->recurring['RecurringMethod'] == 'Installment') {
			if($orderplaced->recurring['indefinite'] == 'yes') {
				$Installments = 999;
			} elseif($orderplaced->recurring['Installments']) {
				$Installments = $orderplaced->recurring['Installments'];
			}
			else {
				$Installments = 999;
			}
			//$Total = number_format($orderplaced->total/$Installments, 2, '.', '');
			$Total = $total_calculate;
		} else {
			$Total = $total_calculate;
		}
		
		
		$GrandTotal = $Total + $ShippingValue;
		$total_amount=$dom->createElement('Total',($GrandTotal*100));
		$total_amount=$trans_totals->appendChild($total_amount);
		
		$strParam =$dom->saveXML();

		return $strParam;
	}

	/**
	* send the Click & Pledge payment request and retrieve and parse the response
	*
	* @return response object from Click & Pledge
	* @param string $xml Click & Pledge payment request as an XML document, as per Click & Pledge specifications
	*/
	private function sendPayment($xml) {
			
		$connect = array('soap_version' => SOAP_1_1, 'trace' => 1, 'exceptions' => 0);
		$client = new SoapClient('https://paas.cloud.clickandpledge.com/paymentservice.svc?wsdl', $connect);
		$soapParams = array('instruction'=>$xml);		 
		$response = $client->Operation($soapParams);

		return $response;
	}
}
