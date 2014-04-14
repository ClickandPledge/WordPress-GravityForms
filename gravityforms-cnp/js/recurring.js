/*!
WordPress plugin gravityforms-cnp
Recurring Payments field
*/

// initialise form on page load
jQuery(function($) {

	var	thisYear = (new Date()).getFullYear(),
		yearRange = thisYear + ":2099",				// year range for max date settings, mumble mumble jquery-ui mumble
		reDatePattern = /^\d{4}-\d\d-\d\d$/,		// regex test for ISO date string
		setPickerOptions = false;

	// set datepicker minimum date if given
	$("input[data-gfcnp-minDate]").each(function() {
		var	input = $(this),
			minDate = this.getAttribute("data-gfcnp-minDate");

		// if minDate is an ISO date string, convert to a Date object as a reliable way to set minDate
		if (reDatePattern.test(minDate)) {
			minDate = new Date(minDate);
		}

		input.datepicker("option", "minDate", minDate);
		setPickerOptions = true;
	});

	// set datepicker maximum date if given
	$("input[data-gfcnp-maxDate]").each(function() {
		var	input = $(this),
			maxDate = this.getAttribute("data-gfcnp-maxDate");

		// if maxDate is an ISO date string, convert to a Date object as a reliable way to set maxDate
		if (reDatePattern.test(maxDate)) {
			maxDate = new Date(maxDate);
		}

		input.datepicker("option", "yearRange", yearRange);		// need to reset year range so can extend max date!
		input.datepicker("option", "maxDate", maxDate);
		setPickerOptions = true;
	});

	// hack: setting options on datepicker fields after initialisation makes the datepicker div show at the bottom of the page
	if (setPickerOptions) {
		$("#ui-datepicker-div").hide();
	}

});
