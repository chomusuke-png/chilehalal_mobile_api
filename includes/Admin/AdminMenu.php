<?php
if (!defined('ABSPATH')) exit;

class ChileHalal_Admin_Menu {

    public function __construct() {
        add_action('admin_menu', [$this, 'registerMainMenu']);
        add_action('admin_menu', [$this, 'fixSubmenuOrder'], 999);
        add_filter('parent_file', [$this, 'fixParentFile']);
        add_filter('submenu_file', [$this, 'fixSubmenuFile']);
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
            'Productos',
            'Productos',
            'manage_options',
            'edit.php?post_type=ch_product'
        );

        add_submenu_page(
            'chilehalal-app',
            'Categorías de Productos',
            'Categorías',
            'manage_options',
            'edit-tags.php?taxonomy=ch_product_category&post_type=ch_product'
        );

        add_submenu_page(
            'chilehalal-app',
            'Negocios',
            'Negocios',
            'manage_options',
            'edit.php?post_type=ch_business'
        );

        add_submenu_page(
            'chilehalal-app',
            'Cupones',
            'Cupones',
            'manage_options',
            'edit.php?post_type=ch_coupon'
        );

        add_submenu_page(
            'chilehalal-app',
            'Usuarios',
            'Usuarios',
            'manage_options',
            'edit.php?post_type=ch_app_user'
        );

        add_submenu_page(
            'chilehalal-app',
            'Historial de Cambios',
            'Historial',
            'manage_options',
            'edit.php?post_type=ch_audit_log'
        );
    }

    public function fixSubmenuOrder() {
        global $submenu;

        if (!isset($submenu['chilehalal-app'])) {
            return;
        }

        $desired_order = [
            'chilehalal-app',
            'edit.php?post_type=ch_product',
            'edit-tags.php?taxonomy=ch_product_category&post_type=ch_product',
            'edit.php?post_type=ch_business',
            'edit.php?post_type=ch_coupon',
            'edit.php?post_type=ch_app_user',
            'edit.php?post_type=ch_audit_log',
        ];

        $indexed = [];
        foreach ($submenu['chilehalal-app'] as $item) {
            $indexed[$item[2]] = $item;
        }

        $sorted = [];
        foreach ($desired_order as $slug) {
            if (isset($indexed[$slug])) {
                $sorted[] = $indexed[$slug];
                unset($indexed[$slug]);
            }
        }

        foreach ($indexed as $item) {
            $sorted[] = $item;
        }

        $submenu['chilehalal-app'] = $sorted;
    }

    public function fixParentFile($parent_file) {
        global $typenow;

        $our_post_types = ['ch_product', 'ch_business', 'ch_coupon', 'ch_app_user', 'ch_audit_log'];

        if (in_array($typenow, $our_post_types)) {
            $parent_file = 'chilehalal-app';
        }

        return $parent_file;
    }

    public function fixSubmenuFile($submenu_file) {
        global $typenow, $pagenow;

        $map = [
            'ch_product'   => 'edit.php?post_type=ch_product',
            'ch_business'  => 'edit.php?post_type=ch_business',
            'ch_coupon'    => 'edit.php?post_type=ch_coupon',
            'ch_app_user'  => 'edit.php?post_type=ch_app_user',
            'ch_audit_log' => 'edit.php?post_type=ch_audit_log',
        ];

        if (in_array($pagenow, ['post-new.php', 'post.php']) && isset($map[$typenow])) {
            $submenu_file = $map[$typenow];
        }

        if ($pagenow === 'term.php' && isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'ch_product_category') {
            $submenu_file = 'edit-tags.php?taxonomy=ch_product_category&post_type=ch_product';
        }

        return $submenu_file;
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