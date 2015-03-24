<div class='wrap'>
<h3>Gravity Forms Click & Pledge Payments</h3>
<?php
//echo '<pre>';
//print_r($this->frm);
?>
<form action="<?php echo $this->scriptURL; ?>" method="post" id="cnp-settings-form">
	<table class="form-table">

		<tr>
			<th>Account ID <span style="color:red;">*</span></th>
			<td>
				<input type='text' class="regular-text" name='AccountID' value="<?php echo esc_attr($this->frm->AccountID); ?>" /><br>
				Get your "Account ID" from Click & Pledge. [Portal > Account Info > API Information].
			</td>
		</tr>

		<tr valign='top'>
			<th>API Account GUID <span style="color:red;">*</span></th>
			<td>
				<input type='text' class="regular-text" name='AccountGuid' value="<?php echo esc_attr($this->frm->AccountGuid); ?>" /><br>
				Get your "API Account GUID" from Click & Pledge [Portal > Account Info > API Information].
			</td>
		</tr>

		<tr valign='top'>
			<th>Mode</th>
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
		
		<tr valign='top' id='OrganizationInformation_tr'>
			<th>Receipt Header</th>
			<td>
				<textarea name="OrganizationInformation" id="OrganizationInformation" class="regular-text" rows="4" cols="53"><?php echo esc_attr($this->frm->OrganizationInformation); ?></textarea><br>
Maximum: 1500 characters, the following HTML tags are allowed:
&lt;P&gt;&lt;/P&gt;&lt;BR /&gt;&lt;OL&gt;&lt;/OL&gt;&lt;LI&gt;&lt;/LI&gt;&lt;UL&gt;&lt;/UL&gt;.  You have <span id="OrganizationInformation_countdown">1500</span> characters left.				
			</td>
		</tr>
		
		<!--<tr valign='top' id='ThankYouMessage_tr'>
			<th>Thank You message</th>
			<td>
				<textarea name="ThankYouMessage" id="ThankYouMessage" class="regular-text" rows="4" cols="53"><?php echo esc_attr($this->frm->ThankYouMessage); ?></textarea><br>
Maximum: 500 characters, the following HTML tags are allowed:
&lt;P&gt;&lt;/P&gt;&lt;BR /&gt;&lt;OL&gt;&lt;/OL&gt;&lt;LI&gt;&lt;/LI&gt;&lt;UL&gt;&lt;/UL&gt;. You have <span id="ThankYouMessage_countdown">500</span> characters left.				
			</td>
		</tr>-->
		
		<tr valign='top' id='TermsCondition_tr'>
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
				$this->frm->available_cards_all = array('Visa' => 'Visa', 'MasterCard' => 'MasterCard', 'Discover' => 'Discover', 'American_Express' => 'American Express',   'JCB' => 'JCB');
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
				
			});
			</script>
			
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
