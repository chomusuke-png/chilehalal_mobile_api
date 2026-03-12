<?php
/**
 * Plugin Name: ChileHalal Mobile API
 * Description: Gestión avanzada de App Móvil.
 * Version: 1.2.4
 * Author: Zumito
 */

if (!defined('ABSPATH')) exit;

define('CH_API_PATH', plugin_dir_path(__FILE__));

if (file_exists(CH_API_PATH . 'vendor/autoload.php')) {
    require_once CH_API_PATH . 'vendor/autoload.php';
}

register_activation_hook(__FILE__, 'chilehalal_activate_plugin');

function chilehalal_activate_plugin() {
    if (!get_option('ch_jwt_secret_db')) {
        $random_key = bin2hex(random_bytes(32));
        update_option('ch_jwt_secret_db', $random_key);
    }
}

function chilehalal_init() {
    $plugin = new ChileHalal_Plugin_Bootstrap();
    $plugin->init();
}
add_action('plugins_loaded', 'chilehalal_init');