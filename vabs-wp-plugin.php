<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://drechsler-development.de
 * @since             1.0.0
 * @package           vabs-wp-plugin
 *
 * @wordpress-plugin
 * Plugin Name:       VABS Form Generator
 * Plugin URI:        https://github.com/vabspro/vabs-beachchair-plugin-php
 * Description:       Provides forms to send data requests to the VABS API
 * Version:           2.0.4
 * Author:            Ronny Drechsler-Hildebrandt
 * Author URI:        https://drechsler-development.de
 * License:           MIT
 * License URI:       https://choosealicense.com/licenses/mit
 * Text Domain:       vabs-wp-plugin
 */

// If this file is called directly, abort.
if (!defined ('WPINC')) {
	die;
}

define ('VABS_PLUGIN_PATH', str_replace (ABSPATH, '/', __DIR__));

require_once 'vendor/autoload.php';
require_once 'config.php';


//For the DD->Database class we need to define some constances
//const DB_HOST = DB_HOST; //Already defined in the wp-config
//const DB_NAME = DB_NAME; //Already defined in the wp-config
//const DB_USER = DB_USER; //Already defined in the wp-config
const DB_PASS = DB_PASSWORD;

use VABS\Plugin;

$Plugin = new Plugin();
$Plugin->Run ();
