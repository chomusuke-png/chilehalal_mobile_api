<?php
/**
 * Plugin Name: ChileHalal Mobile API
 * Description: Gestión avanzada de App Móvil.
 * Version: 1.0.3
 * Author: Zumito
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CH_API_PATH', plugin_dir_path( __FILE__ ) );

if ( file_exists( CH_API_PATH . 'vendor/autoload.php' ) ) {
    require_once CH_API_PATH . 'vendor/autoload.php';
}

function chilehalal_init() {
    new ChileHalal_Admin_Menu();
    new ChileHalal_Product_CPT();
    new ChileHalal_App_User_CPT();
    new ChileHalal_API_Routes();
}
add_action( 'plugins_loaded', 'chilehalal_init' );