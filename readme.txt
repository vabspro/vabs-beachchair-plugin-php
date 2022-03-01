=== VABS :: Beach Chair Plugin ===
Contributors: n/a
Tags: vabs, booking, beachchairs
Donate link: https://drechsler-development.de/donate/
Requires at least: 5.0
Tested up to: 5.9
Requires PHP: 7.4
Stable tag: 1.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Plugin to generate and render booking forms that communicates with the VABS API

== Description ==
This plugins allows you to simply genereate shortcodes you can use on your wordpress pages to show the appropriate booking or kontakt form that sends the data via VABS API to the VABS backend. You will see then the data in the VABS administration webpage as either a new contact with related new lead, a new sales order with sales invoice and the appropriate booking information based on the booking form you have used

This plugin allows you to generate shortcodes for the following forms:

* beach chair booking form

Link to [WordPress](http://wordpress.org/vabs) and one to [Markdown\'s Syntax Documentation][markdown syntax].

== Installation ==
Manually upload
1. Download the vabs-wp-plugin.zip on your local computer
2. Extract the zip file in a temporary folder like \"vabs-wp-plugin\". This folder contains the plugin code
3. Upload the vabs-wp-plugin folder into the wordpress plugin folder on your webserver (via FTPS or sFTP)

Installing via WordPress\' admin page
1. Download the vabs-wp-plugin.zip on your local computer
2. point to the plugin installation page in WordPress
3. Upload the vabs-wp-plugin.zip file
4. click install

Activate the plugin
1. Simply click the activation link

== Frequently Asked Questions ==
= How to get the VABS API keys? =
From the VABS administration page Account->API

= How to get the PayPal API keys? =
From your PayPal developer page

== Changelog ==
= 0.3 =
* add vacancy search on the beach chair booking form in case no booking is available for only one chair. (beach chair hopping)

= 0.2 =
* add PayPal payment integration

= 0.1 =
* Initial release.

== Upgrade Notice ==
= 0.3 =
If you won\'t check your bank transfers daily, you can use the integrated payment system

= 0.2 =
if you want to fill the gaps in your booking list, you can now use beach chair hopping
