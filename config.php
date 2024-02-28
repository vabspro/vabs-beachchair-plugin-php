<?php

@session_start ();

define ('PLUGIN_FOLDER_PATH', dirname (__FILE__));
define ('PLUGIN_FOLDER_NAME', basename (PLUGIN_FOLDER_PATH));

const PLUGIN_FOLDER_NAME_WEB = "/wp-content/plugins/".PLUGIN_FOLDER_NAME;
const EMAIL_DEVELOPER = 'developer@vabs.pro';
define( "SMTP_FROM", 'webseite@'.$_SERVER['HTTP_HOST']);


const PAYMENT_METHOD_INVOICE = 1;
const PAYMENT_METHOD_PAYPAL  = 2;
const PAYMENT_METHOD_STRIPE  = 3;

const SHOW_ERRORS                    = false;
const SETTINGS_TABLE                 = 'beach_vabs_settings';
const PLUGIN_IGNORE_ALREADY_CAPTURED = false;
