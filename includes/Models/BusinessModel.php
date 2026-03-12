<?php
if (!defined('ABSPATH')) exit;

class ChileHalal_Business_Model {

    public function __construct() {
        add_action('init', [$this, 'registerPostType']);
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('save_post', [$this, 'saveMeta']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueMediaUploader']);
    }

    public function registerPostType() {
        register_post_type('ch_business', [
            'labels' => [
                'name' => 'Negocios', 
                'singular_name' => 'Negocio', 
                'add_new' => 'Nuevo Negocio',
                'add_new_item' => 'Añadir Nuevo Negocio'
            ],
            'public' => true,
            'show_in_menu' => 'chilehalal-app',
            'supports' => ['title', 'thumbnail'],
            'menu_icon' => 'dashicons-store',
        ]);
    }

    public function enqueueMediaUploader($hook) {
        global $typenow;
        if ($typenow === 'ch_business') {
            wp_enqueue_media();
        }
    }

    public function addMetaBoxes() {
        // Ahora utilizamos un solo Meta Box grande para centralizar todo
        add_meta_box(
            'ch_business_data', 
            'Configuración del Negocio', 
            [$this, 'renderForm'], 
            'ch_business', 
            'normal', 
            'high'
        );
    }

    public function renderForm($post) {
        // Cargar datos
        $type = get_post_meta($post->ID, '_ch_business_type', true);
        $address = get_post_meta($post->ID, '_ch_business_address', true);
        $phone = get_post_meta($post->ID, '_ch_business_phone', true);
        
        $gallery_ids = get_post_meta($post->ID, '_ch_business_gallery', true) ?: [];
        $menu_json = get_post_meta($post->ID, '_ch_business_menu', true);

        // Llamar a la plantilla
        require CH_API_PATH . 'templates/metaboxes/business-meta.php';
    }

    public function saveMeta($post_id) {
        if (!isset($_POST['ch_business_nonce']) || !wp_verify_nonce($_POST['ch_business_nonce'], 'save_ch_business')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        // Guardar campos básicos (halal_status removido)
        $fields = ['ch_business_type', 'ch_business_address', 'ch_business_phone'];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }

        // Guardar galería
        if (isset($_POST['ch_business_gallery'])) {
            $gallery_raw = explode(',', $_POST['ch_business_gallery']);
            $gallery_clean = array_filter(array_map('intval', $gallery_raw));
            update_post_meta($post_id, '_ch_business_gallery', $gallery_clean);
        }

        // Guardar menú estructurado generado por JS
        if (isset($_POST['ch_business_menu'])) {
            $menu_data = json_decode(stripslashes($_POST['ch_business_menu']), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                update_post_meta($post_id, '_ch_business_menu', json_encode($menu_data, JSON_UNESCAPED_UNICODE));
            }
        }
    }
}