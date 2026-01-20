<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ChileHalal_Admin_Menu {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_main_menu' ] );
    }

    public function register_main_menu() {
        add_menu_page(
            'Gestión App Móvil',
            'ChileHalal Mobile API',
            'manage_options',
            'chilehalal-app',
            [ $this, 'render_dashboard' ],
            'dashicons-smartphone',
            6
        );
    }

    public function render_dashboard() {
        // 1. Preparar datos (Lógica)
        // Contar productos publicados
        $products = wp_count_posts('ch_product');
        $product_count = $products->publish;

        // Contar usuarios (posts)
        $users = wp_count_posts('ch_app_user');
        $user_count = $users->publish + $users->draft + $users->private; // Contamos todos

        // 2. Cargar Vista (Template)
        // Las variables $product_count y $user_count estarán disponibles dentro del archivo incluido
        if ( file_exists( CH_API_PATH . 'templates/admin/dashboard.php' ) ) {
            require_once CH_API_PATH . 'templates/admin/dashboard.php';
        } else {
            echo '<div class="error"><p>Error: No se encuentra el template del dashboard.</p></div>';
        }
    }
}