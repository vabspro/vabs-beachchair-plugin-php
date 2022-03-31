# VABS WordPress Plugin

**Contributors:** n/a  
**Tags:** vabs, booking, beachchairs  
**Requires at least:** 5.0  
**Tested up to:** 5.9  
**Stable tag:** 0.1.0  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html

Here is a short description of the plugin. This should be no more than 150 characters. No markup here.

## Description

This plugins allows you to simply genereate shortcodes you can use on your wordpress pages to show the appropriate
booking or kontakt form that sends the data via VABS API to the VABS backend. You will see then the data in the VABS
administration webpage as either a new contact with related new lead, a new sales order with sales invoice and the
appropriate booking information based on the booking form you have used

This plugin allows you to generate shortcodes for the following forms:

* beach chair booking form

Note that the `readme.txt` of the stable tag is the one that is considered the defining one for the plugin, so if
the `/trunk/readme.txt` file says that the stable tag is `4.3`, then it is `/tags/4.3/readme.txt` that'll be used for
displaying information about the plugin. In this situation, the only thing considered from the trunk `readme.txt`
is the stable tag pointer. Thus, if you develop in trunk, you can update the trunk `readme.txt` to reflect changes in
your in-development version, without having that information incorrectly disclosed about the current stable version
that lacks those changes -- as long as the trunk's `readme.txt` points to the correct stable tag.

If no stable tag is provided, it is assumed that trunk is stable, but you should specify "trunk" if that's where you
put the stable version, in order to eliminate any doubt.

## Installation

*Manually upload*

1. Download the vabs-wp-plugin.zip on your local computer
2. Extract the zip file in a temporary folder like \"temp\" you will see then a new folder named \"vabs-wp-plugin\" in
   that temp folder containing the plugin code. If vabs folder contains another folder please use the second vabs folder
   later on
3. Upload the vabs-wp-plugin folder into the wordpress plugin folder on your webserver (via FTPS or sFTP)

*Installing via WordPress\' admin page*

1. Download the vabs-wp-plugin.zip on your local computer
2. point to the plugin installation page in WordPress
3. Upload the zip file
4. click install

## Activate the plugin

1. Simply click the activation link

== Frequently Asked Questions == = How to get the VABS API keys? = From the VABS administration page Account->API

= How to get the PayPal API keys? = From your PayPal developer page

## Changelog ##

### 2.0 ###

* send invoice even though PayMent via PayPal has not been finished. 

### 1.0 ###

* save settings in a database instead of a file

### 0.3 ###

* add vacancy search on the beach chair booking form in case no booking is available for only one chair. (beach chair
  hopping)

### 0.2 ###

* add PayPal payment integration

### 0.1 ###

* Initial release.
