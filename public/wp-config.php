<?php
// Bootstrap composer and expose env() function
require_once(__DIR__ . '/../vendor/autoload.php');
Env::init();

function mantle_def($key, $default=null) {
	$val = env($key);
	if (isset($default)) define($key, isset($val) ? $val : $default);
	elseif ( !isset($val) ) die("Missing env var: $key");
	else define($key, $val);
}

// Required env vars
array_map('mantle_def', [
    'WP_HOME',
    'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST',
    'AUTH_KEY', 'AUTH_SALT', 'SECURE_AUTH_KEY', 'SECURE_AUTH_SALT',
    'LOGGED_IN_KEY', 'LOGGED_IN_SALT', 'NONCE_KEY', 'NONCE_SALT',
]);

// Optional env vars
array_map('mantle_def',
    ['WP_ENV',     'WP_SITEURL',   'DB_PREFIX', 'DISABLE_WP_CRON' ],
    ['production', WP_HOME . 'wp', 'wp_',       false ]
);

// Fixed values
const
    AUTOMATIC_UPDATER_DISABLED = true,
    DISALLOW_FILE_EDIT = true,
    DB_COLLATE = '',
    DB_CHARSET = 'utf8mb4',
    CONTENT_DIR = '/ext',
    WP_CONTENT_DIR = __DIR__ . CONTENT_DIR
;

// Calculated values
define('WP_CONTENT_URL',    WP_HOME . "ext");

defined('ABSPATH') || define('ABSPATH', __DIR__ . '/wp/');

$table_prefix = DB_PREFIX;

// Environment-specific config files
require_once __DIR__ . '/' . WP_ENV . '-env.php';

// Boot Wordpress
require_once ABSPATH . 'wp-settings.php';
