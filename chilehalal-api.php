<?php
/**
 * Plugin Name: ChileHalal Mobile API
 * Description: Gestión avanzada de App Móvil.
 * Version: 1.0.1
 * Author: Zumito
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CH_API_PATH', plugin_dir_path( __FILE__ ) );

require_once CH_API_PATH . 'includes/admin/class-admin-menu.php';
require_once CH_API_PATH . 'includes/cpt/class-product-cpt.php';
require_once CH_API_PATH . 'includes/cpt/class-app-user-cpt.php';
require_once CH_API_PATH . 'includes/api/class-api-routes.php';

function chilehalal_init() {
    new ChileHalal_Admin_Menu();
    new ChileHalal_Product_CPT();
    new ChileHalal_App_User_CPT();
    new ChileHalal_API_Routes();
}
add_action( 'plugins_loaded', 'chilehalal_init' );