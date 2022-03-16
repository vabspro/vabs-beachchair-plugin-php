<?php

@session_start ();

define ('PLUGIN_FOLDER_PATH', dirname (__FILE__));
define ('PLUGIN_FOLDER_NAME', basename (PLUGIN_FOLDER_PATH));

const PLUGIN_FOLDER_NAME_WEB = "/wp-content/plugins/".PLUGIN_FOLDER_NAME;
const EMAIL_DEVELOPER = 'ronny@vabs.pro';
define( "SMTP_FROM", 'webseite@'.$_SERVER['HTTP_HOST']);
