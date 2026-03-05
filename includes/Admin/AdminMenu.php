<?php
if (!defined('ABSPATH')) exit;

class ChileHalal_Admin_Menu {

    public function __construct() {
        add_action('admin_menu', [$this, 'registerMainMenu']);
        add_action('admin_menu', [$this, 'fixSubmenuOrder'], 999);
    }

    public function registerMainMenu() {
        add_menu_page(
            'Gestión App Móvil',
            'ChileHalal Mobile',
            'manage_options',
            'chilehalal-app',
            [$this, 'renderDashboard'],
            'dashicons-smartphone',
            6
        );
        
        add_submenu_page(
            'chilehalal-app',
            'Panel de Control',
            'Dashboard',
            'manage_options',
            'chilehalal-app',
            [$this, 'renderDashboard']
        );
        
        add_submenu_page(
            'chilehalal-app',
            'Categorías de Productos',
            'Categorías',
            'manage_options',
            'edit-tags.php?taxonomy=ch_product_category&post_type=ch_product'
        );
    }

    public function fixSubmenuOrder() {
        global $submenu;

        if (!isset($submenu['chilehalal-app'])) {
            return;
        }

        $my_submenu = $submenu['chilehalal-app'];
        $dashboard_key = null;

        foreach ($my_submenu as $key => $item) {
            if ($item[2] === 'chilehalal-app') {
                $dashboard_key = $key;
                break;
            }
        }

        if ($dashboard_key !== null) {
            $dashboard_item = $my_submenu[$dashboard_key];
            unset($my_submenu[$dashboard_key]);
            array_unshift($my_submenu, $dashboard_item);
        }

        $submenu['chilehalal-app'] = $my_submenu;
    }

    public function renderDashboard() {
        $products = wp_count_posts('ch_product');
        $product_count = isset($products->publish) ? $products->publish : 0;

        $users = wp_count_posts('ch_app_user');
        $user_count = 0;
        
        if (isset($users->publish)) $user_count += $users->publish;
        if (isset($users->draft))   $user_count += $users->draft;
        if (isset($users->private)) $user_count += $users->private;
        
        $template_path = CH_API_PATH . 'templates/admin/dashboard.php';
        
        if (file_exists($template_path)) {
            require_once $template_path;
        } else {
            echo '<div class="notice notice-error"><p>Error: No se encuentra el archivo de vista del dashboard.</p></div>';
        }
    }
}