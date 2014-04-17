<div class='wrap'>
<h3>Gravity Forms Click & Pledge Payments</h3>
<?php
//echo '<pre>';
//print_r($this->frm);
?>
<form action="<?php echo $this->scriptURL; ?>" method="post" id="cnp-settings-form">
	<table class="form-table">

		<tr>
			<th>C&amp;P Account ID</th>
			<td>
				<input type='text' class="regular-text" name='AccountID' value="<?php echo esc_attr($this->frm->AccountID); ?>" /><br>
				Get your "Account ID" from Click & Pledge. [Portal > Account Info > API Information].
			</td>
		</tr>

		<tr valign='top'>
			<th>C&amp;P API Account GUID</th>
			<td>
				<input type='text' class="regular-text" name='AccountGuid' value="<?php echo esc_attr($this->frm->AccountGuid); ?>" /><br>
				Get your "API Account GUID" from Click & Pledge [Portal > Account Info > API Information].
			</td>
		</tr>

		<tr valign='top'>
			<th>API Mode</th>
			<td>
				<label><input type="radio" name="useTest" value="Y" <?php echo checked($this->frm->useTest, 'Y'); ?> />&nbsp;Test</label>
				&nbsp;&nbsp;<label><input type="radio" name="useTest" value="N" <?php echo checked($this->frm->useTest, 'N'); ?> />&nbsp;Production</label><br>
				Process transactions in Test Mode or Production Mode via the Click & Pledge Test account (www.clickandpledge.com).
			</td>
		</tr>
		
		<tr valign='top'>
			<th>Send Receipt to Patron</th>
			<td>
				<input type="checkbox" name="email_customer" id="email_customer" value="yes" <?php  if(isset($this->frm->email_customer) && $this->frm->email_customer =='yes') { ?>checked<?php } ?>>		
			</td>
		</tr>
		
		<tr valign='top'>
			<th>Organization information</th>
			<td>
				<textarea name="OrganizationInformation" id="OrganizationInformation" class="regular-text" rows="4" cols="53"><?php echo esc_attr($this->frm->OrganizationInformation); ?></textarea><br>
Maximum: 1500 characters, the following HTML tags are allowed:
&lt;P&gt;&lt;/P&gt;&lt;BR /&gt;&lt;OL&gt;&lt;/OL&gt;&lt;LI&gt;&lt;/LI&gt;&lt;UL&gt;&lt;/UL&gt;.  You have <span id="OrganizationInformation_countdown">1500</span> characters left.				
			</td>
		</tr>
		
		<tr valign='top'>
			<th>Thank You message</th>
			<td>
				<textarea name="ThankYouMessage" id="ThankYouMessage" class="regular-text" rows="4" cols="53"><?php echo esc_attr($this->frm->ThankYouMessage); ?></textarea><br>
Maximum: 500 characters, the following HTML tags are allowed:
&lt;P&gt;&lt;/P&gt;&lt;BR /&gt;&lt;OL&gt;&lt;/OL&gt;&lt;LI&gt;&lt;/LI&gt;&lt;UL&gt;&lt;/UL&gt;. You have <span id="ThankYouMessage_countdown">500</span> characters left.				
			</td>
		</tr>
		
		<tr valign='top'>
			<th>Terms & Conditions</th>
			<td>
				<textarea name="TermsCondition" id="TermsCondition" class="regular-text" rows="4" cols="53"><?php echo esc_attr($this->frm->TermsCondition); ?></textarea><br>
To be added at the bottom of the receipt. Typically the text provides proof that the patron has read & agreed to the terms & conditions. The following HTML tags are allowed:
&lt;P&gt;&lt;/P&gt;&lt;BR /&gt;&lt;OL&gt;&lt;/OL&gt;&lt;LI&gt;&lt;/LI&gt;&lt;UL&gt;&lt;/UL&gt;. <br>Maximum: 1500 characters, You have <span id="TermsCondition_countdown">1500</span> characters left.				
			</td>
		</tr>
		
		<tr valign='top' id="Periodicity_tr">
			<th>Accepted Credit Cards</th>
			<td>
				<?php
				$this->frm->available_cards_all = array('Visa' => 'Visa', 'American_Express' => 'American Express', 'Discover' => 'Discover', 'MasterCard' => 'MasterCard', 'JCB' => 'JCB');
				//print_r($this->frm->available_cards);
				foreach($this->frm->available_cards_all as $card => $value) {
				//echo $card.'<br>';
					if(in_array($card, array_keys($this->frm->available_cards))) {
					echo '<input type="checkbox" name="'.$card.'" id="'.$card.'" value="'.$value.'" checked>&nbsp;'.$value.'<br>';
					} else {
					echo '<input type="checkbox" name="'.$card.'" id="'.$card.'" value="'.$value.'">&nbsp;'.$value.'<br>';
					}
				}					
				?>
			</td>
		</tr>
		
		<!--
		<tr valign='top'>
			<th>Recurring Transaction</th>
			<td>
				<label>
				<select name="isRecurring" id="isRecurring">
					<option value="0" <?php if($this->frm->isRecurring == 0) echo 'selected';?>>Disable</option>
					<option value="1" <?php if($this->frm->isRecurring == 1) echo 'selected';?>>Enable</option>
				</select>
				</label>	
			</td>
		</tr>
		
		<tr valign='top' id="Periodicity_tr">
			<th>Periods</th>
			<td>
				<?php
				//print_r($this->frm->available_cards_all);
				$this->frm->available_periods_all = array('Week' => 'Week', 'Weeks_2' => '2 Weeks', 'Month' => 'Month', 'Months_2' => '2 Months', 'Quarter' => 'Quarter', 'Months_6' => '6 Months', 'Year' => 'Year');
				foreach($this->frm->available_periods_all as $card => $value) {
					if(in_array($card, $this->frm->available_periods_all)) {
					echo '<input type="checkbox" name="'.$card.'" id="'.$card.'" value="'.$value.'" checked>&nbsp;'.$value.'<br>';
					} else {
					echo '<input type="checkbox" name="'.$card.'" id="'.$card.'" value="'.$value.'">&nbsp;'.$value.'<br>';
					}
				}					
				?>
			</td>
		</tr>
		
		<tr valign='top' id="RecurringMethod_tr">
			<th>Recurring Methods</th>
			<td>
				<input type="checkbox" name="Subscription" id="Subscription" value="Subscription" <?php if(isset($this->frm->RecurringMethods['Subscription']) && $this->frm->RecurringMethods['Subscription'] == 'Subscription') { ?>checked<?php } ?>>Subscription<br>
				<label id="maxrecurrings_Subscription_label">
					<input type="text" name="maxrecurrings_Subscription" id="maxrecurrings_Subscription" value="<?php echo esc_attr($this->frm->maxrecurrings_Subscription); ?>">Subscription Max. Recurrings Allowed<br>
				</label>
				
				<input type="checkbox" name="Installment" id="Installment" value="Installment" <?php  if(isset($this->frm->RecurringMethods['Installment']) && $this->frm->RecurringMethods['Installment']=='Installment') { ?>checked<?php } ?>>Installment<br>
				<label id="maxrecurrings_Installment_label">
					<input type="text" name="maxrecurrings_Installment" id="maxrecurrings_Installment" value="<?php echo esc_attr($this->frm->maxrecurrings_Installment); ?>">Installment Max. Recurrings Allowed<br>
				</label>				
				
			</td>
		</tr>
		
		<tr valign='top' id="indefinite_tr">
			<th>Enable Indefinite recurring</th>
			<td>
				<input type="checkbox" name="indefinite" id="indefinite" value="yes" <?php  if(isset($this->frm->indefinite) && $this->frm->indefinite =='yes') { ?>checked<?php } ?>>		
			</td>
		</tr>
		-->
		<script>
			
			jQuery(document).ready(function(){
				
				limitText(jQuery('#OrganizationInformation'),jQuery('#OrganizationInformation_countdown'),1500);
				
				limitText(jQuery('#ThankYouMessage'),jQuery('#ThankYouMessage_countdown'),500);
				
				limitText(jQuery('#TermsCondition'),jQuery('#TermsCondition_countdown'),1500);
				
				jQuery( "form" ).submit(function( event ) {
										
					if(jQuery('#AccountID').val() == '')
					{
						alert('Please enter AccountID');
						jQuery('#AccountID').focus();
						return false;
					}
					
					if(jQuery('#AccountGuid').val() == '')
					{
						alert('Please enter AccountGuid');
						jQuery('#AccountGuid').focus();
						return false;
					}
					
					
					var cards = 0;
					if(jQuery('#Visa').is(':checked'))
					{
						cards++;
					}
					if(jQuery('#American_Express').is(':checked'))
					{
						cards++;
					}
					if(jQuery('#Discover').is(':checked'))
					{
						cards++;
					}
					if(jQuery('#MasterCard').is(':checked'))
					{
						cards++;
					}
					if(jQuery('#JCB').is(':checked'))
					{
						cards++;
					}
					
					if(cards == 0) 
					{
						alert('Please select at least  one card');
						jQuery('#Visa').focus();
						return false;
					}
					
					/*
					if(jQuery('#isRecurring').val() == 1)
					{
						if(jQuery('#RecurringLabel').val() == '')
						{
						alert('Please enter Label');
						jQuery('#RecurringLabel').focus();
						return false;
						}
					}
					
					if(jQuery('#Installment').is(':checked') && jQuery('#maxrecurrings_Installment').val() != '')
					{
						if(!jQuery.isNumeric((jQuery('#maxrecurrings_Installment').val())))
						{
							alert('Please enter valid number. It will allow numbers only');
							jQuery('#maxrecurrings_Installment').focus();
							return false;
						}
						if(!isInt(jQuery('#maxrecurrings_Installment').val()))
						{
							alert('Please enter integer values only');
							jQuery('#maxrecurrings_Installment').focus();
							return false;
						}
						if(jQuery('#maxrecurrings_Installment').val() < 2)
						{
							alert('Please enter value greater than 1');
							jQuery('#maxrecurrings_Installment').focus();
							return false;
						}
						if(jQuery('#maxrecurrings_Installment').val() > 998)
						{
							alert('Please enter value between 2-998');
							jQuery('#maxrecurrings_Installment').focus();
							return false;
						}
					}
					function isInt(n) {
						return n % 1 === 0;
					}
					if(jQuery('#Subscription').is(':checked') && jQuery('#maxrecurrings_Subscription').val() != '')
					{
						if(!jQuery.isNumeric((jQuery('#maxrecurrings_Subscription').val())))
						{
						alert('Please enter valid number. It will allow numbers only');
						jQuery('#maxrecurrings_Subscription').focus();
						return false;
						}
						
						if(!isInt(jQuery('#maxrecurrings_Subscription').val()))
						{
							alert('Please enter integer values only');
							jQuery('#maxrecurrings_Subscription').focus();
							return false;
						}
						
						if(jQuery('#maxrecurrings_Subscription').val() < 2)
						{
							alert('Please enter value greater than 1');
							jQuery('#maxrecurrings_Subscription').focus();
							return false;
						}
						if(jQuery('#maxrecurrings_Subscription').val() > 999)
						{
							alert('Please enter value between 2-999');
							jQuery('#maxrecurrings_Subscription').focus();
							return false;
						}
					}
					*/
				});
				
				function limitText(limitField, limitCount, limitNum) {
					if (limitField.val().length > limitNum) {
						limitField.val( limitField.val().substring(0, limitNum) );
					} else {
						limitCount.html (limitNum - limitField.val().length);
					}
				}
				//OrganizationInformation
				jQuery('#OrganizationInformation').keydown(function(){
					limitText(jQuery('#OrganizationInformation'),jQuery('#OrganizationInformation_countdown'),1500);
				});
				jQuery('#OrganizationInformation').keyup(function(){
					limitText(jQuery('#OrganizationInformation'),jQuery('#OrganizationInformation_countdown'),1500);
				});
				//ThankYouMessage
				jQuery('#ThankYouMessage').keydown(function(){
					limitText(jQuery('#ThankYouMessage'),jQuery('#ThankYouMessage_countdown'),500);
				});
				jQuery('#ThankYouMessage').keyup(function(){
					limitText(jQuery('#ThankYouMessage'),jQuery('#ThankYouMessage_countdown'),500);
				});
				//TermsCondition
				jQuery('#TermsCondition').keydown(function(){
					limitText(jQuery('#TermsCondition'),jQuery('#TermsCondition_countdown'),1500);
				});
				jQuery('#TermsCondition').keyup(function(){
					limitText(jQuery('#TermsCondition'),jQuery('#TermsCondition_countdown'),1500);
				});
				/*
				if(jQuery('#isRecurring').val() == 1) {
					jQuery('#Periodicity_tr').show();						
					jQuery('#RecurringMethod_tr').show();						
					jQuery('#indefinite_tr').show();
				} else {
					jQuery('#Periodicity_tr').closest('tr').hide();					
					jQuery('#RecurringMethod_tr').hide();						
					jQuery('#indefinite_tr').hide();
				}
				if(jQuery('#Installment').is(':checked')) {
						jQuery('#maxrecurrings_Installment_label').show();
					} else {
					jQuery('#maxrecurrings_Installment_label').hide();
					}
				jQuery('#Installment').click(function(){
					if(jQuery('#Installment').is(':checked')) {
						jQuery('#maxrecurrings_Installment_label').show();
					} else {
					jQuery('#maxrecurrings_Installment_label').hide();
					}
				});
				
				if(jQuery('#Subscription').is(':checked')) {
						jQuery('#maxrecurrings_Subscription_label').show();
					} else {
					jQuery('#maxrecurrings_Subscription_label').hide();
					}
				jQuery('#Subscription').click(function(){
					if(jQuery('#Subscription').is(':checked')) {
						jQuery('#maxrecurrings_Subscription_label').show();
					} else {
					jQuery('#maxrecurrings_Subscription_label').hide();
					}
				});
				
				jQuery('#isRecurring').change(function(){
					if(jQuery('#isRecurring').val() == 1) {
						jQuery('#Periodicity_tr').show();						
						jQuery('#RecurringMethod_tr').show();
						jQuery('#indefinite_tr').show();						
					} else {
						jQuery('#Periodicity_tr').hide();						
						jQuery('#RecurringMethod_tr').hide();
						jQuery('#indefinite_tr').hide();	
					}
				});
				*/
			});
			</script>
			
		<?php
		/*
		$errNames = array (
			GFCNP_ERROR_ALREADY_SUBMITTED,
			GFCNP_ERROR_NO_AMOUNT,
			GFCNP_ERROR_REQ_CARD_HOLDER,
			GFCNP_ERROR_REQ_CARD_NAME,
			GFCNP_ERROR_EWAY_FAIL,
		);
		foreach ($errNames as $errName) {
			$defmsg = esc_html($this->plugin->getErrMsg($errName, true));
			$msg = esc_attr(get_option($errName));
			?>

			<tr>
				<th><?php echo $defmsg; ?></th>
				<td><input type="text" name="<?php echo esc_attr($errName); ?>" class="large-text" value="<?php echo $msg; ?>" /></td>
			</tr>

			<?php
		}
*/
		?>
	</table>
	<p class="submit">
	<input type="submit" name="Submit" class="button-primary" value="Save Changes" />
	<input type="hidden" name="action" value="save" />
	<?php wp_nonce_field('save', $this->menuPage . '_wpnonce', false); ?>
	</p>
</form>

</div>

<script>
(function($) {

	/**
	* check whether both the sandbox (test) mode and Stored Payments are selected,
	* show warning message if they are
	*/
	function setVisibility() {
		var	useTest = ($("input[name='useTest']:checked").val() == "Y"),
			useBeagle = ($("input[name='useBeagle']:checked").val() == "Y"),
			useStored = ($("input[name='useStored']:checked").val() == "Y");

		function display(element, visible) {
			if (visible)
				element.css({display: "none"}).show(750);
			else
				element.hide();
		}

		display($("#gfeway-opt-admin-stored-test"), (useTest && useStored));
		display($("#gfeway-opt-admin-stored-beagle"), (useBeagle && useStored));
		display($("#gfeway-opt-admin-beagle-address"), useBeagle);
	}

	$("#cnp-settings-form").on("change", "input[name='useTest'],input[name='useBeagle'],input[name='useStored']", setVisibility);

	//setVisibility();

})(jQuery);
</script>
