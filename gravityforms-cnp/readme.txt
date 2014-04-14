=== Gravity Forms Click & Pledge ===
Contributors: Click & Pledge
Plugin Name: Gravity Forms Click & Pledge
Plugin URI: http://clickandpledge.com/
Author URI: http://clickandpledge.com/
Tags: gravityforms, gravity forms, gravity, cnp, donation, donations, payment, recurring, ecommerce, credit cards, click & pledge, pledge
Requires at least: 3.7.1
Tested up to: 3.8.1
Stable tag: 1.0
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

== Screenshots ==

1. Options screen
2. A sample donation form
3. The sample donation form as it appears on a page
4. How a credit card validation error appears
5. A successful entry in Gravity Forms admin
6. Example with recurring payments
7. Forcing SSL on a page with a credit card form