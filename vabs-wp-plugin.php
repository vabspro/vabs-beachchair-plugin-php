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
 * Plugin URI:        https://github.com/g-kanoufi/wp-cleanup-and-basic-functions
 * Description:       Provides forms to send data request to the VABS API
 * Version:           1.0.0
 * Author:            Ronny Drechsler-Hildebrandt
 * Author URI:        https://drechsler-development.de
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 * Text Domain:       vabs-wp-plugin
 */

// If this file is called directly, abort.
if (!defined ('WPINC')) {
	die;
}

define ('VABS_PLUGIN_PATH', str_replace (ABSPATH, '/', __DIR__));

require_once 'vendor/autoload.php';
require_once 'config.php';

use VABS\Plugin;

$Plugin = new Plugin();
$Plugin->Run ();
