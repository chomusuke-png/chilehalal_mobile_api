<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ChileHalal_App_User_CPT {

    public function __construct() {
        add_action( 'init', [ $this, 'register_user_cpt' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post', [ $this, 'save_meta' ] );
    }

    public function register_user_cpt() {
        register_post_type( 'ch_app_user', [
            'labels' => [ 'name' => 'Usuarios App', 'singular_name' => 'Usuario', 'add_new' => 'Nuevo Usuario' ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'chilehalal-app',
            'supports' => [ 'title' ],
            'menu_icon' => 'dashicons-people',
        ]);
    }

    public function add_meta_boxes() {
        add_meta_box( 'ch_user_data', 'Datos de la Cuenta', [ $this, 'render_form' ], 'ch_app_user', 'normal', 'high' );
    }

    // --- CARGAMOS EL TEMPLATE ---
    public function render_form( $post ) {
        // 1. Preparar Datos
        $email = get_post_meta( $post->ID, '_ch_user_email', true );
        $phone = get_post_meta( $post->ID, '_ch_user_phone', true );
        $status = get_post_meta( $post->ID, '_ch_user_status', true );
        $points = get_post_meta( $post->ID, '_ch_user_points', true );

        // 2. Cargar Template
        require CH_API_PATH . 'templates/metaboxes/user-meta.php';
    }

    public function save_meta( $post_id ) {
        if ( ! isset( $_POST['ch_user_nonce'] ) || ! wp_verify_nonce( $_POST['ch_user_nonce'], 'save_ch_user' ) ) return;
        
        $fields = ['ch_user_email', 'ch_user_phone', 'ch_user_status', 'ch_user_points'];
        foreach ( $fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
            }
        }
    }
}