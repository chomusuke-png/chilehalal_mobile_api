<?php

if ( ! defined( 'ABSPATH' ) ) exit;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ChileHalal_API_Routes {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    // --- CLAVE JWT ---
    private function get_jwt_secret() {
        if ( defined( 'CH_JWT_SECRET' ) ) {
            return CH_JWT_SECRET;
        }
        $db_secret = get_option( 'ch_jwt_secret_db' );
        if ( ! empty( $db_secret ) ) {
            return $db_secret;
        }
        return 'clave_de_emergencia_temporal_generar_nueva_por_favor';
    }

    // --- RUTAS ---
    public function register_routes() {
        // Catálogo de Productos (GET)
        register_rest_route( 'chilehalal/v1', '/products', [
            'methods'  => 'GET',
            'callback' => [ $this, 'handle_get_products' ],
            'permission_callback' => '__return_true', // O usa check_jwt_permission si quieres que sea privado
        ]);

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

        // Datos del usuario (GET)
        register_rest_route( 'chilehalal/v1', '/user/me', [
            'methods'  => 'GET',
            'callback' => [ $this, 'handle_get_me' ],
            'permission_callback' => [ $this, 'check_jwt_permission' ],
        ]);
    }

    // --- MIDDLEWARE DE SEGURIDAD JWT ---
    public function check_jwt_permission( $request ) {
        $auth_header = $request->get_header( 'authorization' );
        
        if ( ! $auth_header ) {
            return new WP_Error( 'no_token', 'Token de autorización no encontrado', ['status' => 401] );
        }

        $token = str_replace( 'Bearer ', '', $auth_header );

        try {
            $decoded = JWT::decode( $token, new Key( $this->get_jwt_secret(), 'HS256' ) );
            
            $request->set_param( 'user_id', $decoded->data->user_id );
            
            return true;

        } catch ( Exception $e ) {
            return new WP_Error( 'invalid_token', 'Token inválido o expirado: ' . $e->getMessage(), ['status' => 401] );
        }
    }

    // --- HANDLER DEL CATÁLOGO ---
    public function handle_get_products( $request ) {
        $page = $request->get_param('page') ?: 1;

        $args = [
            'post_type'      => 'ch_product',
            'posts_per_page' => 20,
            'paged'          => $page,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC'
        ];

        $query = new WP_Query( $args );
        $products = [];

        if ( $query->have_posts() ) {
            foreach ( $query->posts as $post ) {
                $products[] = [
                    'id'          => $post->ID,
                    'name'        => $post->post_title,
                    'description' => wp_strip_all_tags( get_post_meta($post->ID, '_ch_description', true) ),
                    'brand'       => get_post_meta($post->ID, '_ch_brand', true),
                    'is_halal'    => get_post_meta( $post->ID, '_ch_is_halal', true ) === 'yes',
                    'barcode'     => get_post_meta( $post->ID, '_ch_barcode', true ),
                    'image_url'   => get_the_post_thumbnail_url( $post->ID, 'medium' ) ?: null
                ];
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'data'    => $products
        ], 200);
    }

    // --- HANDLER DEL ESCÁNER ---
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

    // --- HANDLER DE REGISTRO ---
    public function handle_register( $request ) {
        $params = $request->get_json_params();
        $email = sanitize_email( $params['email'] ?? '' );
        $password = $params['password'] ?? '';
        $name = sanitize_text_field( $params['name'] ?? '' );

        if ( empty($email) || empty($password) || empty($name) ) return new WP_Error( 'missing_fields', 'Faltan datos', ['status' => 400] );
        if ( ! is_email($email) ) return new WP_Error( 'invalid_email', 'Correo inválido', ['status' => 400] );

        $existing = new WP_Query([
            'post_type' => 'ch_app_user', 'meta_key' => '_ch_user_email', 'meta_value' => $email, 'posts_per_page' => 1, 'post_status' => 'any'
        ]);
        if ( $existing->have_posts() ) return new WP_Error( 'user_exists', 'Correo ya registrado', ['status' => 409] );

        $post_id = wp_insert_post([ 'post_title' => $name, 'post_type' => 'ch_app_user', 'post_status' => 'publish' ]);
        if ( is_wp_error( $post_id ) ) return new WP_Error( 'db_error', 'Error al crear usuario', ['status' => 500] );

        update_post_meta( $post_id, '_ch_user_email', $email );
        update_post_meta( $post_id, '_ch_user_status', 'active' );
        update_post_meta( $post_id, '_ch_user_role', 'user' );
        update_post_meta( $post_id, '_ch_user_pass_hash', wp_hash_password( $password ) );

        return new WP_REST_Response([ 'success' => true, 'message' => 'Usuario registrado' ], 201);
    }

    // --- HANDLER DE LOGIN ---
    public function handle_login( $request ) {
        $params = $request->get_json_params();
        $email = sanitize_email( $params['email'] ?? '' );
        $password = $params['password'] ?? '';

        if ( empty($email) || empty($password) ) return new WP_Error( 'missing_fields', 'Datos requeridos', ['status' => 400] );

        $query = new WP_Query([
            'post_type' => 'ch_app_user', 'meta_key' => '_ch_user_email', 'meta_value' => $email, 'posts_per_page' => 1
        ]);
        if ( ! $query->have_posts() ) return new WP_Error( 'invalid_auth', 'Credenciales incorrectas', ['status' => 401] );

        $user_post = $query->posts[0];
        if ( get_post_meta( $user_post->ID, '_ch_user_status', true ) === 'banned' ) return new WP_Error( 'user_banned', 'Cuenta bloqueada', ['status' => 403] );

        $stored_hash = get_post_meta( $user_post->ID, '_ch_user_pass_hash', true );
        
        if ( $stored_hash && wp_check_password( $password, $stored_hash ) ) {
            $issued_at = time();
            $expiration = $issued_at + ( 60 * 60 * 24 * 7 );
            $role = get_post_meta( $user_post->ID, '_ch_user_role', true ) ?: 'user';

            $payload = [
                'iss'  => get_bloginfo( 'url' ),
                'iat'  => $issued_at,
                'exp'  => $expiration,
                'data' => [ 'user_id' => $user_post->ID, 'email' => $email, 'role' => $role ] // Rol en el token es útil
            ];

            $token = JWT::encode( $payload, $this->get_jwt_secret(), 'HS256' );

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'id'     => $user_post->ID,
                    'name'   => $user_post->post_title,
                    'email'  => $email,
                    'role'   => $role,
                    'token'  => $token
                ]
            ], 200);
        } else {
            return new WP_Error( 'invalid_auth', 'Credenciales incorrectas', ['status' => 401] );
        }
    }

    // --- HANDLER DE USUARIO ---
    public function handle_get_me( $request ) {
        $user_id = $request->get_param( 'user_id' );
        $post = get_post( $user_id );
        $role = get_post_meta( $user_id, '_ch_user_role', true ) ?: 'user';

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'id' => $user_id,
                'name' => $post->post_title,
                'role' => $role,
                'status' => get_post_meta( $user_id, '_ch_user_status', true )
            ]
        ], 200);
    }
}