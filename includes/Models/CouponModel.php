<?php
if (!defined('ABSPATH')) exit;

class ChileHalal_Coupon_Model {

    public function __construct() {
        add_action('init', [$this, 'registerPostType']);
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('save_post', [$this, 'saveMeta']);
    }

    public function registerPostType() {
        register_post_type('ch_coupon', [
            'labels' => [
                'name' => 'Cupones', 
                'singular_name' => 'Cupón', 
                'add_new' => 'Nuevo Cupón',
                'add_new_item' => 'Añadir Nuevo Cupón'
            ],
            'public' => true,
            'show_in_menu' => 'chilehalal-app',
            'supports' => ['title'],
            'menu_icon' => 'dashicons-tickets-alt',
        ]);
    }

    public function addMetaBoxes() {
        add_meta_box(
            'ch_coupon_details', 
            'Configuración del Cupón', 
            [$this, 'renderForm'], 
            'ch_coupon', 
            'normal', 
            'high'
        );
    }

    public function renderForm($post) {
        $business_id = get_post_meta($post->ID, '_ch_coupon_business_id', true);
        $discount = get_post_meta($post->ID, '_ch_coupon_discount', true);
        $code = get_post_meta($post->ID, '_ch_coupon_code', true);
        $expiry = get_post_meta($post->ID, '_ch_coupon_expiry', true);
        $status = get_post_meta($post->ID, '_ch_coupon_status', true);

        // Obtener todos los negocios publicados para el dropdown
        $businesses = get_posts(['post_type' => 'ch_business', 'posts_per_page' => -1, 'post_status' => 'publish']);

        require CH_API_PATH . 'templates/metaboxes/coupon-meta.php';
    }

    public function saveMeta($post_id) {
        if (!isset($_POST['ch_coupon_nonce']) || !wp_verify_nonce($_POST['ch_coupon_nonce'], 'save_ch_coupon')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        $fields = ['ch_coupon_business_id', 'ch_coupon_discount', 'ch_coupon_code', 'ch_coupon_expiry', 'ch_coupon_status'];
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $val = $field === 'ch_coupon_code' ? strtoupper(sanitize_text_field($_POST[$field])) : sanitize_text_field($_POST[$field]);
                update_post_meta($post_id, '_' . $field, $val);
            }
        }
    }
}