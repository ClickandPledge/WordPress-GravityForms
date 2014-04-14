<?php

/**
* class for managing form data
*/
class GFCnpFormData {

	public $amount = 0;
	public $total = 0;
	public $ccName = '';
	public $ccNumber = '';
	public $ccExpMonth = '';
	public $ccExpYear = '';
	public $ccCVN = '';
	public $namePrefix = '';
	public $firstName = '';
	public $lastName = '';
	public $email = '';
	public $address = '';						// simple address, for regular payments
	public $address_street = '';				// street address, for recurring payments
	public $address_suburb = '';				// suburb, for recurring payments
	public $address_state = '';					// state, for recurring payments
	public $address_country = '';				// country, for recurring payments
	public $postcode = '';						// postcode, for both regular and recurring payments
	
	//Shipping address
	public $address_shipping = '';						// simple address, for regular payments
	public $address_street_shipping = '';				// street address, for recurring payments
	public $address_suburb_shipping = '';				// suburb, for recurring payments
	public $address_state_shipping = '';					// state, for recurring payments
	public $address_country_shipping = '';				// country, for recurring payments
	public $postcode_shipping = '';						// postcode, for both regular and recurring payments
	
	public $phone = '';							// phone number, for recurring payments
	public $recurring = FALSE;					// false, or an array of inputs from complex field
	public $ccField = FALSE;					// handle to meta-"field" for credit card in form
	
	public $productdetails = array();
	public $customfields = array();
	public $needtovalidatefields = array();
	public $shippingfields = array();
	
	//Duplicate fields checking
	public $creditcardCount = 0;
	public $shippingCount = 0;
	public $recurringCount = 0;
	
	private $isLastPageFlag = FALSE;
	private $isCcHiddenFlag = FALSE;
	private $hasPurchaseFieldsFlag = FALSE;

	/**
	* initialise instance
	* @param array $form
	*/
	public function __construct(&$form) {
		// check for last page
        $current_page = GFFormDisplay::get_source_page($form['id']);
        $target_page = GFFormDisplay::get_target_page($form, $current_page, rgpost('gform_field_values'));
        $this->isLastPageFlag = ($target_page == 0);

		// load the form data
		$this->loadForm($form);
	}
	
	
	/**
	* load the form data we care about from the form array
	* @param array $form
	*/
	private function loadForm(&$form) {
		
		foreach ($form['fields'] as &$field) {
			$id = $field['id'];
			//echo RGFormsModel::get_input_type($field).'<br>';
			switch(RGFormsModel::get_input_type($field)){
				case 'name':
					// only pick up the first name field (assume later ones are additional info)
					if (empty($this->firstName) && empty($this->lastName)) {
						$this->namePrefix = rgpost("input_{$id}_2");
						$this->firstName = rgpost("input_{$id}_3");
						$this->lastName = rgpost("input_{$id}_6");
					}
					else
					{
						$item_custom['FieldName'] = $field["label"];
						$anothername = rgpost("input_{$id}_2");
						if(rgpost("input_{$id}_3"))
						$anothername .= ' ' . rgpost("input_{$id}_3");
						if(rgpost("input_{$id}_6"))
						$anothername .= ' ' . rgpost("input_{$id}_6");
						$item_custom['FieldValue'] = $anothername;
						if($item_custom['FieldValue'])
						$this->customfields[] = $item_custom;
					}
					break;
					
				
				case 'email':
					// only pick up the first email address field (assume later ones are additional info)
					if (empty($this->email)) {
						$this->email = rgpost("input_{$id}");
					}
					else {
						$item_custom['FieldName'] = $field["label"];
						$item_custom['FieldValue'] = rgpost("input_{$id}");
						if($item_custom['FieldValue'])
						$this->customfields[] = $item_custom;
					}
					break;

				case 'phone':
					// only pick up the first phone number field (assume later ones are additional info)
					if (empty($this->phone)) {
						$this->phone = rgpost("input_{$id}");
					} else {
						$item_custom['FieldName'] = $field["label"];
						$item_custom['FieldValue'] = rgpost("input_{$id}");
						if($item_custom['FieldValue'])
						$this->customfields[] = $item_custom;
					}
					break;

				case 'address':
					// only pick up the first address field (assume later ones are additional info, e.g. shipping)
					if (empty($this->address) && empty($this->postcode)) 
					{
						$this->postcode = trim(rgpost("input_{$id}_5"));
						$parts = array(trim(rgpost("input_{$id}_1")), trim(rgpost("input_{$id}_2")));
						$this->address_street = implode(', ', array_filter($parts, 'strlen'));
						$this->address_suburb = trim(rgpost("input_{$id}_3"));
						$this->address_state = trim(rgpost("input_{$id}_4"));
						$this->address_country = trim(rgpost("input_{$id}_6"));

						// aggregate street, city, state, country into a single string (for regular one-off payments)
						$parts = array($this->address_street, $this->address_suburb, $this->address_state, $this->address_country);
						$this->address = implode(', ', array_filter($parts, 'strlen'));
					}
					elseif(empty($this->address_shipping) && empty($this->postcode_shipping)) 
					{
						$this->postcode_shipping = trim(rgpost("input_{$id}_5"));
						$parts = array(trim(rgpost("input_{$id}_1")), trim(rgpost("input_{$id}_2")));
						$this->address_street_shipping = implode(', ', array_filter($parts, 'strlen'));
						$this->address_suburb_shipping = trim(rgpost("input_{$id}_3"));
						$this->address_state_shipping = trim(rgpost("input_{$id}_4"));
						$this->address_country_shipping = trim(rgpost("input_{$id}_6"));

						// aggregate street, city, state, country into a single string (for regular one-off payments)
						$parts = array($this->address_street_shipping, $this->address_suburb_shipping, $this->address_state_shipping, $this->address_country_shipping);
						$this->address_shipping = implode(', ', array_filter($parts, 'strlen'));
					}
					else
					{
						$item_custom['FieldName'] = $field["label"];
						$parts = array(trim(rgpost("input_{$id}_1")), trim(rgpost("input_{$id}_2")));
						$str1 = implode(', ', array_filter($parts, 'strlen'));
						$parts = array($str, trim(rgpost("input_{$id}_3")), trim(rgpost("input_{$id}_4")), trim(rgpost("input_{$id}_6")));
						$str2 = implode(', ', array_filter($parts, 'strlen'));
						
						$item_custom['FieldValue'] = implode(', ', array_filter($str2, 'strlen'));
						if($item_custom['FieldValue'])
						$this->customfields[] = $item_custom;
					}
					break;

				case 'creditcard':
					$this->creditcardCount++;
					$this->isCcHiddenFlag = RGFormsModel::is_field_hidden($form, $field, RGForms::post('gform_field_values'));
					$this->ccField =& $field;
					$this->ccName = rgpost("input_{$id}_5");
					$this->ccNumber = self::cleanCcNumber(rgpost("input_{$id}_1"));
					$ccExp = rgpost("input_{$id}_2");
					if (is_array($ccExp))
						list($this->ccExpMonth, $this->ccExpYear) = $ccExp;
					$this->ccCVN = rgpost("input_{$id}_3");
					break;

				case 'total':
					$this->total = GFCommon::to_number(rgpost("input_{$id}"));
					$this->hasPurchaseFieldsFlag = true;
					break;
				

				case GFCNP_FIELD_RECURRING:
					$this->recurringCount++;
					// only pick it up if it isn't hidden
					if (!RGFormsModel::is_field_hidden($form, $field, RGForms::post('gform_field_values'))) {
						$this->recurring = GFCnpRecurringField::getPost($id);
						//print_r(GFCnpRecurringField::getPost($id));
						//print_r($_POST);
						//die();
					}
					break;

				default:
					// check for product field
					if (GFCommon::is_product_field($field['type']) || $field['type'] == 'donation') {
						$this->amount += self::getProductPrice($form, $field);
						$this->hasPurchaseFieldsFlag = true;						
					}
					elseif(!GFCommon::is_post_field($field)) 
					//else
					{				
						switch($field['type'])
						{
							case 'checkbox':
								$inputs = $field['inputs'];
								$str = '';								
								for($c = 1; $c <= count($inputs); $c++) {
									$val = rgpost("input_{$id}_{$c}");
									if($val) $str .= $val . ',';
								}								
								$item_custom['FieldName'] = $field["label"];
								$item_custom['FieldValue'] = $str;
							break;
							case 'radio':
								
								$str = rgpost("input_{$id}");								
								$item_custom['FieldName'] = $field["label"];
								$item_custom['FieldValue'] = $str;								
							break;
							case 'html':
							case 'section':
							case 'page':
							case 'captcha':
							break;
							default:
							
								$item_custom['FieldName'] = $field["label"];
								$temp = rgpost("input_{$id}");
								$val = '';
								if(is_array($temp)) 
								{
									if($field["type"] == 'time')
									{									
									if(isset($temp[0]))
										$val = $temp[0];
									if(isset($temp[1]))
										$val .= ':'.$temp[1];
									if(isset($temp[2]))
										$val .= ' '.$temp[2];				
									}
									else
									{
									$val = implode(', ', $temp);
									}
								}
								else
								{
								$val = $temp;
								}
								
								$item_custom['FieldValue'] = $val;
															
						}
						if($item_custom['FieldValue'])
						$this->customfields[] = $item_custom;																	
					}
					break;
			}
		}

		// TODO: shipping?
		
		//print_r($this->options);
//die('FOrm Data');
		// if form didn't pass the total, pick it up from calculated amount
		if ($this->total == 0)
			$this->total = $this->amount;
	}

	/**
	* extract the price from a product field, and multiply it by the quantity
	* @return float
	*/
	public function getProductPrice($form, $field) {
		$price = $qty = 0;
		$isProduct = false;
		$id = $field['id'];
		$item = array();
		$item_custom = array();
		$item_validate = array();
		$item_shipping = array();
//needtovalidatefields

		if (!RGFormsModel::is_field_hidden($form, $field, array())) {
			$lead_value = rgpost("input_{$id}");

			$qty_field = GFCommon::get_product_fields_by_type($form, array('quantity'), $id);
			$qty = sizeof($qty_field) > 0 ? rgpost("input_{$qty_field[0]['id']}") : 1;
//print_r($field);
//echo '<br>';
			switch ($field["inputType"]) {
				case 'singleproduct':
				case 'calculation':
					 $price = GFCommon::to_number(rgpost("input_{$id}_2"));
					 $qty = GFCommon::to_number(rgpost("input_{$id}_3"));
					 //$item_custom['FieldValue'] = rgpost("input_{$id}");
					
					$isProduct = true;
					if($qty) 
					{
						$item['ItemName'] = $field["label"];
						$item['ItemID'] = $field["id"];
						$item['Quantity'] = $qty;
						$item['UnitPrice'] = $price;
						$item['productField'] = $field["productField"];
						$t = $item['productField'];
						if($t)
						$item['OptionValue'] = rgpost("input_{$t}");
					}
					
					break;
				
				case 'singleshipping':				
					$this->shippingCount++;
					$price = GFCommon::to_number($field["basePrice"]);
					$isProduct = true;					
					$item_shipping['ShippingMethod'] = $field["label"];
					$item_shipping['ShippingValue'] = $price;						
					break;
				case 'hiddenproduct':
					$price = GFCommon::to_number($field["basePrice"]);
					$isProduct = true;
					
					$item['ItemName'] = $field["label"];
					$item['ItemID'] = $field["id"];
					$qty = GFCommon::to_number(rgpost("input_{$id}_3"));
					$item['Quantity'] = $qty;
					$item['UnitPrice'] = $price;
					$item['productField'] = $field["productField"];
					$t = $item['productField'];
					if($t)
					$item['OptionValue'] = rgpost("input_{$t}");
					
					if($price) {
					$item_validate['rule'] = 'price';
					$item_validate['type'] = 'price';
					$item_validate['value'] = $price;
					}
					break;
				case 'donation':
				case 'price':					
					$price = GFCommon::to_number($lead_value);
					$isProduct = true;
					$item['ItemName'] = $field["label"];
					$item['ItemID'] = $field["id"];
					$item['Quantity'] = $qty;
					$item['UnitPrice'] = $price;
					$item['productField'] = $field["productField"];
					$t = $item['productField'];
					if($t)
					$item['OptionValue'] = rgpost("input_{$t}");
					
					if($price) {
					$item_validate['rule'] = 'price';
					$item_validate['type'] = 'price';
					$item_validate['value'] = $price;
					}
					break;
				case 'number':		//This case will handle the 'Quantity' field					
					$id = $field["productField"];
					$price = GFCommon::to_number(rgpost("input_{$id}_2"));
					$isProduct = true;
					$item['ItemName'] = trim(rgpost("input_{$id}_1"));
					$item['ItemID'] = $id;
					$item['Quantity'] = GFCommon::to_number($lead_value);
					$item['UnitPrice'] = $price;
					$item['productField'] = $field["productField"];
					$t = $item['productField'];
					if($t)
					$item['OptionValue'] = rgpost("input_{$t}");
					
					if($price) {
					$item_validate['rule'] = 'price';
					$item_validate['type'] = 'price';
					$item_validate['value'] = $price;
					}
					break;
				default:						
					
					// handle drop-down lists and radio buttons
					if($field["type"] == 'shipping')
					{
					$this->shippingCount++;
					$price = GFCommon::to_number($field["basePrice"]);
					$isProduct = true;
					list($name, $price) = rgexplode('|', $lead_value, 2);
					$item_shipping['ShippingMethod'] = $name;
					$item_shipping['ShippingValue'] = $price;	
					}
					
					elseif (!empty($lead_value)) {
//echo $lead_value;
						list($name, $price) = rgexplode('|', $lead_value, 2);
						$isProduct = true;
						$item['ItemName'] = $field["label"];
						$item['ItemID'] = $field["id"];
						$item['Quantity'] = $qty;
						$item['UnitPrice'] = $price;						
						//$item_custom['FieldName'] = $field["label"];
						//$item_custom['FieldValue'] = $name;
						$item['productField'] = $field["productField"];
						echo $t = $item['productField'];
						if($t)
						$item['OptionValue'] = $name;
						else
						$item['OptionValue'] = $name;
					}
					
					break;
			}

			// pick up extra costs from any options
			if ($isProduct) {
				$options = GFCommon::get_product_fields_by_type($form, array('option'), $id);
				//echo '<pre>';
				//print_r($options);
				//die();
				foreach($options as $option){
					if (!RGFormsModel::is_field_hidden($form, $option, array())) {
						$option_value = rgpost("input_{$option['id']}");

						if (is_array(rgar($option, 'inputs'))) {
							foreach($option['inputs'] as $input){
								$input_value = rgpost('input_' . str_replace('.', '_', $input['id']));
								$option_info = GFCommon::get_option_info($input_value, $option, true);
								if(!empty($option_info))
									$price += GFCommon::to_number(rgar($option_info, 'price'));
							}
						}
						elseif (!empty($option_value)){
							$option_info = GFCommon::get_option_info($option_value, $option, true);
							$price += GFCommon::to_number(rgar($option_info, 'price'));
						}
					}
				}
			}

			$price *= $qty;
		}

		//echo $field["inputType"].'<br>';
		//die();
		
		//print_r($item_custom);
		//die();
		if(count($item))
		$this->productdetails[$id] = $item;
		if(count($item_custom))
		$this->customfields[] = $item_custom;
		if(count($item_validate))
		$this->needtovalidatefields[] = $item_validate;
		if(count($item_shipping))
		$this->shippingfields[] = $item_shipping;
		
		return $price;
	}

	/**
	* clean up credit card number, removing spaces and dashes, so that it should only be digits if correctly submitted
	* @param string $ccNumber
	* @return string
	*/
	private static function cleanCcNumber($ccNumber) {
		return strtr($ccNumber, array(' ' => '', '-' => ''));
	}

	/**
	* check whether we're on the last page of the form
	* @return boolean
	*/
	public function isLastPage() {
		return $this->isLastPageFlag;
	}

	/**
	* check whether CC field is hidden (which indicates that payment is being made another way)
	* @return boolean
	*/
	public function isCcHidden() {
		return $this->isCcHiddenFlag;
	}

	/**
	* check whether form has any product fields or a recurring payment field (because CC needs something to bill against)
	* @return boolean
	*/
	public function hasPurchaseFields() {
		return $this->hasPurchaseFieldsFlag || !!$this->recurring;
	}

	/**
	* check whether form a recurring payment field
	* @return boolean
	*/
	public function hasRecurringPayments() {
		return !!$this->recurring;
	}
}
