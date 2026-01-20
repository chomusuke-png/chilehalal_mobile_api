<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class ChileHalal_Admin_Menu {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_main_menu' ] );
    }

    public function register_main_menu() {
        add_menu_page(
            'Gestión App Móvil',
            'ChileHalal Mobile',
            'manage_options',
            'chilehalal-app',
            [ $this, 'render_dashboard' ],
            'dashicons-smartphone',
            6
        );
        add_submenu_page(
            'chilehalal-app',
            'Panel de Control',
            'Dashboard',
            'manage_options',
            'chilehalal-app',
            [ $this, 'render_dashboard' ]
        );
    }

    public function render_dashboard() {
        $products = wp_count_posts('ch_product');
        $product_count = $products->publish;

        $users = wp_count_posts('ch_app_user');
        
        $user_count = 0;
        if ( isset( $users->publish ) ) $user_count += $users->publish;
        if ( isset( $users->draft ) )   $user_count += $users->draft;
        if ( isset( $users->private ) ) $user_count += $users->private;
        
        if ( file_exists( CH_API_PATH . 'templates/admin/dashboard.php' ) ) {
            require_once CH_API_PATH . 'templates/admin/dashboard.php';
        } else {
            echo '<div class="notice notice-error"><p>Error: No se encuentra el archivo <code>templates/admin/dashboard.php</code>.</p></div>';
        }
    }
}