<?php
if (!defined('ABSPATH')) exit;

use Firebase\JWT\JWT;

class ChileHalal_Auth_Controller {
    public function register($request) {
        $params = $request->get_json_params();
        $email = sanitize_email($params['email'] ?? '');
        $password = $params['password'] ?? '';
        $name = sanitize_text_field($params['name'] ?? '');

        if (empty($email) || empty($password) || empty($name)) {
            return new WP_Error('missing_fields', 'Faltan datos', ['status' => 400]);
        }
        
        if (!is_email($email)) {
            return new WP_Error('invalid_email', 'Correo inválido', ['status' => 400]);
        }

        $existing = new WP_Query([
            'post_type' => 'ch_app_user',
            'meta_key' => '_ch_user_email',
            'meta_value' => $email,
            'posts_per_page' => 1,
            'post_status' => 'any'
        ]);
        
        if ($existing->have_posts()) {
            return new WP_Error('user_exists', 'Correo ya registrado', ['status' => 409]);
        }

        $post_id = wp_insert_post([
            'post_title' => $name, 
            'post_type' => 'ch_app_user', 
            'post_status' => 'publish'
        ]);
        
        if (is_wp_error($post_id)) {
            return new WP_Error('db_error', 'Error al crear usuario', ['status' => 500]);
        }

        update_post_meta($post_id, '_ch_user_email', $email);
        update_post_meta($post_id, '_ch_user_status', 'active');
        update_post_meta($post_id, '_ch_user_role', 'user');
        update_post_meta($post_id, '_ch_user_pass_hash', wp_hash_password($password));

        return new WP_REST_Response(['success' => true, 'message' => 'Usuario registrado'], 201);
    }

    public function login($request) {
        $params = $request->get_json_params();
        $email = sanitize_email($params['email'] ?? '');
        $password = $params['password'] ?? '';

        if (empty($email) || empty($password)) {
            return new WP_Error('missing_fields', 'Datos requeridos', ['status' => 400]);
        }

        $query = new WP_Query([
            'post_type' => 'ch_app_user',
            'meta_key' => '_ch_user_email',
            'meta_value' => $email,
            'posts_per_page' => 1
        ]);
        
        if (!$query->have_posts()) {
            return new WP_Error('invalid_auth', 'Credenciales incorrectas', ['status' => 401]);
        }

        $user_post = $query->posts[0];
        $stored_hash = get_post_meta($user_post->ID, '_ch_user_pass_hash', true);

        if ($stored_hash && wp_check_password($password, $stored_hash)) {
            $role = get_post_meta($user_post->ID, '_ch_user_role', true) ?: 'user';
            
            $payload = [
                'iss' => get_bloginfo('url'),
                'iat' => time(),
                'exp' => time() + (60 * 60 * 24 * 7),
                'data' => [
                    'user_id' => $user_post->ID, 
                    'email' => $email, 
                    'role' => $role
                ]
            ];

            $token = JWT::encode($payload, ChileHalal_API_Middleware::get_jwt_secret(), 'HS256');

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'id' => $user_post->ID,
                    'name' => $user_post->post_title,
                    'role' => $role,
                    'token' => $token
                ]
            ], 200);
        }
        
        return new WP_Error('invalid_auth', 'Credenciales incorrectas', ['status' => 401]);
    }
}