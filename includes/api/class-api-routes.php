<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class ChileHalal_API_Routes {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        // Escáner (GET)
        register_rest_route( 'chilehalal/v1', '/scan/(?P<barcode>[a-zA-Z0-9-]+)', [
            'methods'  => 'GET',
            'callback' => [ $this, 'handle_scan' ],
            'permission_callback' => '__return_true',
        ]);

        // Registro (POST)
        register_rest_route( 'chilehalal/v1', '/auth/register', [
            'methods'  => 'POST',
            'callback' => [ $this, 'handle_register' ],
            'permission_callback' => '__return_true',
        ]);

        // Login (POST)
        register_rest_route( 'chilehalal/v1', '/auth/login', [
            'methods'  => 'POST',
            'callback' => [ $this, 'handle_login' ],
            'permission_callback' => '__return_true',
        ]);
    }

    // --- LÓGICA DEL ESCÁNER ---
    public function handle_scan( $request ) {
        $barcode = $request['barcode'];
        
        $args = [
            'post_type' => 'ch_product',
            'posts_per_page' => 1,
            'meta_key' => '_ch_barcode',
            'meta_value' => $barcode,
            'post_status' => 'publish'
        ];

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            $post = $query->posts[0];
            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'name'        => $post->post_title,
                    'description' => wp_strip_all_tags( get_post_meta($post->ID, '_ch_description', true) ),
                    'brand'       => get_post_meta($post->ID, '_ch_brand', true),
                    'is_halal'    => get_post_meta( $post->ID, '_ch_is_halal', true ) === 'yes',
                    'image_url'   => get_the_post_thumbnail_url( $post->ID, 'medium' ) ?: null
                ]
            ], 200);
        }

        return new WP_REST_Response([ 'success' => false, 'message' => 'Producto no encontrado' ], 404);
    }

    // --- LÓGICA DE REGISTRO ---
    public function handle_register( $request ) {
        $params = $request->get_json_params();
        $email = sanitize_email( $params['email'] ?? '' );
        $password = $params['password'] ?? '';
        $name = sanitize_text_field( $params['name'] ?? '' );

        if ( empty($email) || empty($password) || empty($name) ) {
            return new WP_Error( 'missing_fields', 'Faltan datos obligatorios', ['status' => 400] );
        }

        if ( ! is_email($email) ) {
            return new WP_Error( 'invalid_email', 'El correo no es válido', ['status' => 400] );
        }

        $existing_user = new WP_Query([
            'post_type' => 'ch_app_user',
            'meta_key' => '_ch_user_email',
            'meta_value' => $email,
            'posts_per_page' => 1,
            'post_status' => ['publish', 'draft', 'private']
        ]);

        if ( $existing_user->have_posts() ) {
            return new WP_Error( 'user_exists', 'Este correo ya está registrado', ['status' => 409] );
        }

        $post_id = wp_insert_post([
            'post_title'  => $name,
            'post_type'   => 'ch_app_user',
            'post_status' => 'publish',
        ]);

        if ( is_wp_error( $post_id ) ) {
            return new WP_Error( 'db_error', 'No se pudo crear el usuario', ['status' => 500] );
        }

        update_post_meta( $post_id, '_ch_user_email', $email );
        update_post_meta( $post_id, '_ch_user_status', 'active' );
        update_post_meta( $post_id, '_ch_user_points', 0 );
        
        update_post_meta( $post_id, '_ch_user_pass_hash', wp_hash_password( $password ) );

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Usuario registrado exitosamente',
            'user_id' => $post_id
        ], 201);
    }

    // --- LÓGICA DE LOGIN ---
    public function handle_login( $request ) {
        $params = $request->get_json_params();
        $email = sanitize_email( $params['email'] ?? '' );
        $password = $params['password'] ?? '';

        if ( empty($email) || empty($password) ) {
            return new WP_Error( 'missing_fields', 'Email y contraseña requeridos', ['status' => 400] );
        }

        $query = new WP_Query([
            'post_type' => 'ch_app_user',
            'meta_key' => '_ch_user_email',
            'meta_value' => $email,
            'posts_per_page' => 1
        ]);

        if ( ! $query->have_posts() ) {
            return new WP_Error( 'invalid_auth', 'Credenciales incorrectas', ['status' => 401] );
        }

        $user_post = $query->posts[0];
        
        $status = get_post_meta( $user_post->ID, '_ch_user_status', true );
        if ( $status === 'banned' ) {
            return new WP_Error( 'user_banned', 'Tu cuenta ha sido bloqueada', ['status' => 403] );
        }

        $stored_hash = get_post_meta( $user_post->ID, '_ch_user_pass_hash', true );
        
        if ( $stored_hash && wp_check_password( $password, $stored_hash ) ) {
            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'id'     => $user_post->ID,
                    'name'   => $user_post->post_title,
                    'email'  => $email,
                    'points' => (int) get_post_meta( $user_post->ID, '_ch_user_points', true ),
                    'token'  => 'simulated_jwt_token_' . $user_post->ID
                ]
            ], 200);
        } else {
            return new WP_Error( 'invalid_auth', 'Credenciales incorrectas', ['status' => 401] );
        }
    }
}