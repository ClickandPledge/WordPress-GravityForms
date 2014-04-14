<?php
/*
Plugin Name: Gravity Forms Click & Pledge
Plugin URI: http://clickandpledge.com/
Description: Integrates Gravity Forms with Click & Pledge payment gateway, enabling end users to purchase goods and services through Gravity Forms.
Version: 1.0
Author: Click & Pledge
Author URI: http://manual.clickandpledge.com/
*/


/*
useful references:
http://www.gravityhelp.com/forums/topic/credit-card-validating#post-44438
http://www.gravityhelp.com/documentation/page/Gform_creditcard_types
http://www.gravityhelp.com/documentation/page/Gform_enable_credit_card_field
http://www.gravityhelp.com/documentation/page/Form_Object
http://www.gravityhelp.com/documentation/page/Entry_Object
*/

if (!defined('GFCNP_PLUGIN_ROOT')) {
	define('GFCNP_PLUGIN_ROOT', dirname(__FILE__) . '/');
	define('GFCNP_PLUGIN_NAME', basename(dirname(__FILE__)) . '/' . basename(__FILE__));
	define('GFCNP_PLUGIN_OPTIONS', 'gfcnp_plugin');

	// script/style version
	if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG)
		define('GFCNP_PLUGIN_VERSION', time());
	else
		define('GFCNP_PLUGIN_VERSION', '1.0');

	// error message names
	define('GFCNP_ERROR_ALREADY_SUBMITTED', 'gfcnp_err_already');
	define('GFCNP_ERROR_NO_AMOUNT', 'gfcnp_err_no_amount');
	define('GFCNP_ERROR_REQ_CARD_HOLDER', 'gfcnp_err_req_card_holder');
	define('GFCNP_ERROR_REQ_CARD_NAME', 'gfcnp_err_req_card_name');
	define('GFCNP_ERROR_CNP_FAIL', 'gfcnp_err_cnp_fail');

	// custom fields
	define('GFCNP_FIELD_RECURRING', 'gfcnprecurring');
}

/**
* autoload classes as/when needed
*
* @param string $class_name name of class to attempt to load
*/
function gfcnp_autoload($class_name) {
	static $classMap = array (
		'GFCnpAdmin'						=> 'class.GFCnpAdmin.php',
		'GFCnpFormData'					=> 'class.GFCnpFormData.php',
		'GFCnpOptionsAdmin'				=> 'class.GFCnpOptionsAdmin.php',
		'GFCnpPayment'						=> 'class.GFCnpPayment.php',
		'GFCnpPlugin'						=> 'class.GFCnpPlugin.php',
		'GFCnpRecurringField'				=> 'class.GFCnpRecurringField.php',
		'GFCnpRecurringPayment'			=> 'class.GFCnpRecurringPayment.php',
		'GFCnpStoredPayment'				=> 'class.GFCnpStoredPayment.php',
	);

	if (isset($classMap[$class_name])) {
		require GFCNP_PLUGIN_ROOT . $classMap[$class_name];
	}
}
spl_autoload_register('gfcnp_autoload');

// instantiate the plug-in
GFCnpPlugin::getInstance();
