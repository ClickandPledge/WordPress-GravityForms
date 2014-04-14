/*!
WordPress plugin gravityforms-cnp
form editor for Recurring Payments field
*/
;var GFCnpRecurring=(function(a){return{SetFieldLabel:function(d,b){var c=d.value;if(!(/\S/.test(c))){c=b}a("."+d.id).text(c);SetFieldProperty(d.id,c)},

ToggleInitialSetting:function(b){SetFieldProperty(b.id,b.checked);if(b.checked){a("#gfcnp_initial_fields").slideDown()}else{a("#gfcnp_initial_fields").slideUp()}},

ToggleInstallmentSetting:function(b){SetFieldProperty(b.id,b.checked);if(b.checked){a("#maxrecurrings_Installment_label").show()}else{a("#maxrecurrings_Installment_label").hide()}},

ToggleSubscriptionSetting:function(b){SetFieldProperty(b.id,b.checked);if(b.checked){a("#maxrecurrings_Subscription_label").show()}else{a("#maxrecurrings_Subscription_label").hide()}},


FieldSet:function(b){SetFieldProperty(b.id,b.value);},

FieldSet2:function(b){ SetFieldProperty(b.id,b.checked); },

ToggleRecurringDateSetting:function(b){SetFieldProperty(b.id,b.checked);}}})(jQuery);

jQuery(function(a){fieldSettings.gfcnprecurring=".conditional_logic_field_setting, .error_message_setting, .label_setting, .admin_label_setting, .rules_setting, .description_setting, .css_class_setting, .gfcnprecurring_setting";

a(document).bind("gform_load_field_settings",function(c,d,b){
var first_load = true;
if(typeof(d.gfcnp_maxrecurrings_Installment) != 'undefined') first_load = false;
if(typeof(d.gfcnp_maxrecurrings_Subscription) != 'undefined') first_load = false;

if(typeof(d.gfcnp_Week_setting) != 'undefined') { first_load = false;}
if(typeof(d.gfcnp_2_Weeks_setting) != 'undefined') { first_load = false;}
if(typeof(d.gfcnp_Month_setting) != 'undefined') {  first_load = false;}
if(typeof(d.gfcnp_2_Months_setting) != 'undefined') { first_load = false;}
if(typeof(d.gfcnp_Quarter_setting) != 'undefined') {  first_load = false;}
if(typeof(d.gfcnp_6_Months_setting) != 'undefined') { first_load = false;}
if(typeof(d.gfcnp_Year_setting) != 'undefined') { first_load = false;}

if(typeof(d.Subscription) != 'undefined'){ first_load = false; }
if(typeof(d.Installment) != 'undefined'){ first_load = false; }
if(typeof(d.indefinite) != 'undefined') { first_load = false; }

if(first_load)
{
	SetFieldProperty('gfcnp_maxrecurrings_Installment', a('#gfcnp_maxrecurrings_Installment').val());
	SetFieldProperty('gfcnp_maxrecurrings_Subscription', a('#gfcnp_maxrecurrings_Subscription').val());
	//console.log(a('#gfcnp_Week_setting').checked)
	SetFieldProperty('gfcnp_Week_setting', true);
	SetFieldProperty('gfcnp_2_Weeks_setting', true);
	SetFieldProperty('gfcnp_Month_setting', true);
	SetFieldProperty('gfcnp_2_Months_setting', true);
	SetFieldProperty('gfcnp_Quarter_setting', true);
	SetFieldProperty('gfcnp_6_Months_setting', true);
	SetFieldProperty('gfcnp_Year_setting', true);
	
	SetFieldProperty('Subscription', false);
	SetFieldProperty('Installment', false);
	SetFieldProperty('indefinite', false);
}
//console.log(d);
if(!first_load)
{
	//If the form is not first time we need to clear the form and then assign the values
	a("#gfcnp_maxrecurrings_Installment").val('');
	a("#gfcnp_maxrecurrings_Subscription").val('');
	a("#gfcnp_Week_setting").prop('checked', false);
	a("#gfcnp_2_Weeks_setting").prop('checked', false);
	a("#gfcnp_Month_setting").prop('checked', false);
	a("#gfcnp_2_Months_setting").prop('checked', false);
	a("#gfcnp_Quarter_setting").prop('checked', false);
	a("#gfcnp_6_Months_setting").prop('checked', false);
	a("#gfcnp_Year_setting").prop('checked', false);
	
	//Assign values
	a("#gfcnp_maxrecurrings_Installment").val(d.gfcnp_maxrecurrings_Installment);
	a("#gfcnp_maxrecurrings_Subscription").val(d.gfcnp_maxrecurrings_Subscription);
	
	if(d.gfcnp_Week_setting == '1') { a("#gfcnp_Week_setting").prop('checked', true); }
	if(d.gfcnp_2_Weeks_setting == '1') { a("#gfcnp_2_Weeks_setting").prop('checked', true); }
	if(d.gfcnp_Month_setting == '1') { a("#gfcnp_Month_setting").prop('checked', true); }
	if(d.gfcnp_2_Months_setting == '1') { a("#gfcnp_2_Months_setting").prop('checked', true); }
	if(d.gfcnp_Quarter_setting == '1') { a("#gfcnp_Quarter_setting").prop('checked', true); }
	if(d.gfcnp_6_Months_setting == '1') { a("#gfcnp_6_Months_setting").prop('checked', true); }
	if(d.gfcnp_Year_setting == '1') { a("#gfcnp_Year_setting").prop('checked', true); }
	
	if(d.Subscription == '1')
	{
	a("#Subscription").prop('checked', true);
	a("#maxrecurrings_Subscription_label").show();
	a("#gfcnp_maxrecurrings_Subscription").val(d.gfcnp_maxrecurrings_Subscription);
	}
	if(d.Installment == '1')
	{
	a("#Installment").prop('checked', true);
	a("#maxrecurrings_Installment_label").show();
	a("#gfcnp_maxrecurrings_Installment").val(d.gfcnp_maxrecurrings_Installment);
	}

	if(d.indefinite == '1')
	{
	a("#indefinite").prop('checked', true);
	}
}

a("#gfcnp_initial_setting").prop("checked",!!d.gfcnp_initial_setting);

if(!d.gfcnp_initial_setting){a("#gfcnp_initial_fields").hide()}

a("#gfcnp_recurring_date_setting").prop("checked",!!d.gfcnp_recurring_date_setting);

if(!d.gfcnp_recurring_date_setting){a("#gfcnp_recurring_date_fields").hide()}a("#gfcnp_initial_amount_label").val(d.gfcnp_initial_amount_label);a("#gfcnp_recurring_amount_label").val(d.gfcnp_recurring_amount_label);a("#gfcnp_initial_date_label").val(d.gfcnp_initial_date_label);a("#gfcnp_start_date_label").val(d.gfcnp_start_date_label);a("#gfcnp_end_date_label").val(d.gfcnp_end_date_label);a("#gfcnp_interval_type_label").val(d.gfcnp_interval_type_label)})});