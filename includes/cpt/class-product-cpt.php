<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ChileHalal_Product_CPT {

    public function __construct() {
        add_action( 'init', [ $this, 'register_post_type' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post', [ $this, 'save_meta' ] );
    }

    public function register_post_type() {
        register_post_type( 'ch_product', [
            'labels' => [ 'name' => 'Productos App', 'singular_name' => 'Producto', 'add_new' => 'Escanear Nuevo' ],
            'public' => true,
            'show_in_menu' => 'chilehalal-app',
            'supports' => [ 'title', 'thumbnail' ], 
            'menu_icon' => 'dashicons-cart',
        ]);
    }

    public function add_meta_boxes() {
        add_meta_box( 'ch_product_details', 'Ficha Técnica del Producto', [ $this, 'render_form' ], 'ch_product', 'normal', 'high' );
    }

    // --- AQUÍ ESTÁ EL CAMBIO PRINCIPAL ---
    public function render_form( $post ) {
        // 1. Preparar Datos
        $barcode = get_post_meta( $post->ID, '_ch_barcode', true );
        $is_halal = get_post_meta( $post->ID, '_ch_is_halal', true );
        $brand = get_post_meta( $post->ID, '_ch_brand', true );
        $description = get_post_meta( $post->ID, '_ch_description', true );

        // 2. Cargar Template
        require CH_API_PATH . 'templates/metaboxes/product-meta.php';
    }

    public function save_meta( $post_id ) {
        if ( ! isset( $_POST['ch_product_nonce'] ) || ! wp_verify_nonce( $_POST['ch_product_nonce'], 'save_ch_product' ) ) return;
        
        $fields = ['ch_barcode', 'ch_is_halal', 'ch_brand', 'ch_description'];
        foreach ( $fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, sanitize_textarea_field( $_POST[ $field ] ) );
            }
        }
    }
}