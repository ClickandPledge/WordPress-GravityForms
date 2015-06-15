=== Gravity Forms Click & Pledge ===
Contributors: Click & Pledge
Plugin Name: Gravity Forms Click & Pledge
Plugin URI: http://clickandpledge.com/
Author URI: http://clickandpledge.com/
Tags: gravityforms, gravity forms, gravity, cnp, donation, donations, payment, recurring, ecommerce, credit cards, click & pledge, pledge
Requires at least: 3.7.1
Tested up to: 4.2.2
Stable tag: 2.100.004.000.20150511
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add a credit card payment gateway for Click & Pledge to the Gravity Forms plugin

== Description ==
Gravity Forms Click & Pledge adds a credit card payment gateway for to the [Gravity Forms](http://www.gravityforms.com/) plugin.
* build online donation forms
* build online booking forms
* build simple Buy Now forms
* accept recurring payments

> NB: this plugin extends [Gravity Forms](http://www.gravityforms.com/); you still need to install and activate Gravity Forms!

Thanks for sponsoring new features on Gravity Forms Click & Pledge!

= Requirements: =
* you need to install the [Gravity Forms](http://www.gravityforms.com/) plugin
* you need an SSL certificate for your hosting account
* you need an account with Click & Pledge

== Installation ==

1. Install and activate the [Gravity Forms](http://www.gravityforms.com/) plugin
2. Upload the Gravity Forms CnP plugin to your /wp-content/plugins/ directory.
3. Activate the Gravity Forms CnP plugin through the 'Plugins' menu in WordPress.
4. Edit the CnP payment gateway settings to set your Click & Pledge Account ID, API Account GUID and options

Gravity Forms will now display the Credit Card and Recurring fields under Pricing Fields when you edit a form.

= Building a Form with Credit Card Payments =

* add one or more product fields or a total field, or a recurring field, so that there is something to be charged by credit card
* add an email field and an address field if you want to see them on your Click & Pledge transaction; the first email field and first address field on the form will be sent to Click & Pledge
* add a credit card field; if you have a multi-page form, this must be the on the last page so that all other form validations occur first
* add a confirmation message to the form indicating that payment was successful; the form will not complete if payment was not successful, and will display an error message in the credit card field

== Changelog ==
-----------------------------------------------------------------------------
Version 2.100.004.000.20150511
- Fixed SOAP Client issue (Ref:https://forums.clickandpledge.com/showthread.php?t=2545&p=9533#post9533)
- Simple load XML issue

-----------------------------------------------------------------------------
Version 2.100.003.000.20150506
- Fixed Ip address issue

-----------------------------------------------------------------------------
Version 2.100.002.000.20150505
- Fixed SKU issue (don’t allow & sign in the SKU)
- Fixed First Name issue (Ref:https://forums.clickandpledge.com/showthread.php?t=2542)

-----------------------------------------------------------------------------
Version 2.100.001.000.20150420
- Fixed shipping method issue 

-----------------------------------------------------------------------------
Version 2.100.000.000.20150324    
- Handled to avoid replace of 'js.php' file. (Ref:https://github.com/ClickandPledge/WordPress-GravityForms/issues/1)
- Fixed Minor issues
- Added custom labels for Recurring field (Ref:https://forums.clickandpledge.com/showthread.php?t=2297)
- Compatible with Gravity Forms 1.9.4.9
- Updated March 24, 2015

-----------------------------------------------------------------------------
Version 2.0.8    
- Fixed Permissions issue.
- Updated Dec 03, 2014

-----------------------------------------------------------------------------
Version 2.0.7    
- Fixed SKU issue.
- Updated Nov 17, 2014

-----------------------------------------------------------------------------
Version 2.0.6    
- Fixed special characters issue.
- Fixed recurring field issue (Not adding to the form).
- Updated Oct 07, 2014

-----------------------------------------------------------------------------
Version 2.0.5    
- Fixed warning message when form submit. (Ref:https://forums.clickandpledge.com/showthread.php?t=2070&p=7732)
- Update the 'js.php' for not to add more than one 'recurring' or 'echeck' fields to the same form.
- Updated Sep 25, 2014

-----------------------------------------------------------------------------
Version 2.0.4    
- Fixed issue while adding a form to C&P gateway.
- Updated July 21, 2014

-----------------------------------------------------------------------------
Version 2.0.3    
- Fixed Duplicate custom field issue.
- Updated July 10, 2014

-----------------------------------------------------------------------------
Version 2.0.2    
- Fixed UI issues in recurring field.
- Updated June 25, 2014

-----------------------------------------------------------------------------
Version 2.0.1    
- Fixed UI issues in recurring field.

-----------------------------------------------------------------------------
Version 2.0.0    
- Added eCheck feature.

-----------------------------------------------------------------------------
Version 1.2.1    
- Fixed issue for special characters handling in XML.
- Added Form ID while assigning forms to C&P Payment Gateway form  

-----------------------------------------------------------------------------
Version 1.2    
- Fixed issue while saving payment options in admin.  

-----------------------------------------------------------------------------
Version 1.1    
- Fixed validation issue on using multi payment gateways.    
- Added support for multiple payment gateways to be configured using the same form and executed based on condition.

-----------------------------------------------------------------------------
Version 1.0.beta    
- Added ability to specify mode (Production or Test) on settings page

== Frequently Asked Questions ==
<strong>Click & Pledge Overview</strong><br>
<a href="https://forums.clickandpledge.com/content.php?r=255-Click-Pledge-Webinar" target="_blank"> <strong>Recorded Webinar</strong></a><br>
<br>

<strong>Other helpful resources:</strong>
<a href="https://forums.clickandpledge.com/" target="_blank">Click & Pledge Forum</a>
<a href="https://forums.clickandpledge.com/list.php?r=category/71-How-to-Videos" target="_blank">Click & Pledge 'How To' Videos</a>
<a href="http://manual.clickandpledge.com/" target="_blank">Click & Pledge Manual</a>