/*!
WordPress plugin gravityforms-cnp
form editor for Recurring Payments field
*/

// create namespace to avoid collisions
var GFCnpRecurring = (function($) {

	return {
		/**
		* set the label on a subfield in the recurring field
		* @param {HTMLElement} field
		* @param {String} defaultValue
		*/
		SetFieldLabel : function(field, defaultValue) {
			var newLabel = field.value;

			// if new label value is empty, pick up the default value instead
			if (!(/\S/.test(newLabel)))
				newLabel = defaultValue;

			// set the new label, and record for the field
			$("." + field.id).text(newLabel);
			SetFieldProperty(field.id, newLabel);
		},

		/**
		* toggle whether to show the Initial Amount and Initial Date fields
		* @param {HTMLElement} field
		*/
		ToggleInitialSetting : function(field) {
			SetFieldProperty(field.id, field.checked);
			if (field.checked) {
				$("#gfcnp_initial_fields").slideDown();
			}
			else {
				$("#gfcnp_initial_fields").slideUp();
			}
		},

		/**
		* toggle whether to show the Start Date and End Date fields
		* @param {HTMLElement} field
		*/
		ToggleRecurringDateSetting : function(field) {
			SetFieldProperty(field.id, field.checked);
			if (field.checked) {
				$("#gfcnp_recurring_date_fields").slideDown();
			}
			else {
				$("#gfcnp_recurring_date_fields").slideUp();
			}
		}
	};

})(jQuery);

// initialise form on page load
jQuery(function($) {

	// add required classes to the field on the admin form
	fieldSettings.gfcnprecurring = ".conditional_logic_field_setting, .error_message_setting, .label_setting, .admin_label_setting, .rules_setting, .description_setting, .css_class_setting, .gfcnpsku_setting";

	// binding to the load field settings event to initialize custom inputs
	$(document).bind("gform_load_field_settings", function(event, field, form) {

		$("#gfcnp_initial_setting").prop("checked", !!field.gfcnp_initial_setting);
		if (!field.gfcnp_initial_setting) {
			$("#gfcnp_initial_fields").hide();
		}

		$("#gfcnp_recurring_date_setting").prop("checked", !!field.gfcnp_recurring_date_setting);
		if (!field.gfcnp_recurring_date_setting) {
			$("#gfcnp_recurring_date_fields").hide();
		}

	});

});
