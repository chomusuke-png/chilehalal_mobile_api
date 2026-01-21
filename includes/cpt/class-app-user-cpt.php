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
            'labels' => [ 'name' => 'Usuarios', 'singular_name' => 'Usuario', 'add_new' => 'Nuevo Usuario' ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'chilehalal-app',
            'supports' => [ 'title' ],
        ]);
    }

    public function add_meta_boxes() {
        add_meta_box( 'ch_user_data', 'Datos de la Cuenta', [ $this, 'render_form' ], 'ch_app_user', 'normal', 'high' );
    }

    public function render_form( $post ) {
        $email = get_post_meta( $post->ID, '_ch_user_email', true );
        $phone = get_post_meta( $post->ID, '_ch_user_phone', true );
        $status = get_post_meta( $post->ID, '_ch_user_status', true );
        
        $role = get_post_meta( $post->ID, '_ch_user_role', true );
        if ( empty( $role ) ) $role = 'user'; 

        require CH_API_PATH . 'templates/metaboxes/user-meta.php';
    }

    public function save_meta( $post_id ) {
        if ( ! isset( $_POST['ch_user_nonce'] ) || ! wp_verify_nonce( $_POST['ch_user_nonce'], 'save_ch_user' ) ) return;
        
        // Guardar campos normales
        $fields = ['ch_user_email', 'ch_user_phone', 'ch_user_status', 'ch_user_role'];
        foreach ( $fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
            }
        }

        // borrar en un futuro
        if ( ! empty( $_POST['ch_user_new_pass'] ) ) {
            $raw_pass = $_POST['ch_user_new_pass'];
            $hashed_pass = wp_hash_password( $raw_pass );
            update_post_meta( $post_id, '_ch_user_pass_hash', $hashed_pass );
        }
    }
}