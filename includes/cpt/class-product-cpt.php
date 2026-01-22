<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ChileHalal_Product_CPT {

    public function __construct() {
        add_action( 'init', [ $this, 'register_post_type' ] );
        add_action( 'init', [ $this, 'register_taxonomies' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post', [ $this, 'save_meta' ] );
    }

    public function register_post_type() {
        register_post_type( 'ch_product', [
            'labels' => [ 'name' => 'Productos', 'singular_name' => 'Producto', 'add_new' => 'Nuevo Producto' ],
            'public' => true,
            'show_in_menu' => 'chilehalal-app',
            'supports' => [ 'title', 'thumbnail' ],
            'taxonomies' => [ 'ch_product_category' ]
        ]);
    }

    public function register_taxonomies() {
        register_taxonomy( 'ch_product_category', 'ch_product', [
            'labels' => [
                'name' => 'Categorías',
                'singular_name' => 'Categoría',
                'add_new_item' => 'Añadir Nueva Categoría'
            ],
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => [ 'slug' => 'categoria-producto' ],
        ]);
    }

    public function add_meta_boxes() {
        add_meta_box( 'ch_product_details', 'Ficha Técnica del Producto', [ $this, 'render_form' ], 'ch_product', 'normal', 'high' );
    }

    public function render_form( $post ) {
        $barcode = get_post_meta( $post->ID, '_ch_barcode', true );
        $is_halal = get_post_meta( $post->ID, '_ch_is_halal', true );
        $brand = get_post_meta( $post->ID, '_ch_brand', true );
        $description = get_post_meta( $post->ID, '_ch_description', true );

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