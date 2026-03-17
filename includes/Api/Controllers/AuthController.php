<?php
if (!defined('ABSPATH')) exit;

use Firebase\JWT\JWT;

class ChileHalal_Auth_Controller {

    private static $max_attempts   = 5;
    private static $lockout_seconds = 15 * MINUTE_IN_SECONDS;

    private function getRateLimitKey($email) {
        return 'ch_login_attempts_' . md5(strtolower(trim($email)));
    }

    private function getAttempts($email) {
        $data = get_transient($this->getRateLimitKey($email));
        return $data ?: ['count' => 0, 'first_attempt' => time()];
    }

    private function incrementAttempts($email) {
        $key  = $this->getRateLimitKey($email);
        $data = $this->getAttempts($email);
        $data['count']++;
        set_transient($key, $data, self::$lockout_seconds);
    }

    private function clearAttempts($email) {
        delete_transient($this->getRateLimitKey($email));
    }

    private function isLockedOut($email) {
        $data = $this->getAttempts($email);
        return $data['count'] >= self::$max_attempts;
    }

    public function register($request) {
        $params   = $request->get_json_params();
        $email    = sanitize_email($params['email'] ?? '');
        $password = $params['password'] ?? '';
        $name     = sanitize_text_field($params['name'] ?? '');

        if (empty($email) || empty($password) || empty($name)) {
            return new WP_Error('missing_fields', 'Faltan datos', ['status' => 400]);
        }

        if (!is_email($email)) {
            return new WP_Error('invalid_email', 'Correo inválido', ['status' => 400]);
        }

        if (strlen($password) < 8) {
            return new WP_Error('weak_password', 'La contraseña debe tener al menos 8 caracteres.', ['status' => 400]);
        }

        $existing = new WP_Query([
            'post_type'      => 'ch_app_user',
            'meta_key'       => '_ch_user_email',
            'meta_value'     => $email,
            'posts_per_page' => 1,
            'post_status'    => 'any',
        ]);

        if ($existing->have_posts()) {
            return new WP_Error('user_exists', 'Correo ya registrado', ['status' => 409]);
        }

        $post_id = wp_insert_post([
            'post_title'  => $name,
            'post_type'   => 'ch_app_user',
            'post_status' => 'publish',
        ]);

        if (is_wp_error($post_id)) {
            return new WP_Error('db_error', 'Error al crear usuario', ['status' => 500]);
        }

        update_post_meta($post_id, '_ch_user_email',     $email);
        update_post_meta($post_id, '_ch_user_status',    'active');
        update_post_meta($post_id, '_ch_user_role',      'user');
        update_post_meta($post_id, '_ch_user_pass_hash', wp_hash_password($password));

        return new WP_REST_Response(['success' => true, 'message' => 'Usuario registrado'], 201);
    }

    public function login($request) {
        $params   = $request->get_json_params();
        $email    = sanitize_email($params['email'] ?? '');
        $password = $params['password'] ?? '';

        if (empty($email) || empty($password)) {
            return new WP_Error('missing_fields', 'Datos requeridos', ['status' => 400]);
        }

        if ($this->isLockedOut($email)) {
            return new WP_Error(
                'too_many_attempts',
                'Demasiados intentos fallidos. Intenta de nuevo en 15 minutos.',
                ['status' => 429]
            );
        }

        $query = new WP_Query([
            'post_type'      => 'ch_app_user',
            'meta_key'       => '_ch_user_email',
            'meta_value'     => $email,
            'posts_per_page' => 1,
        ]);

        if (!$query->have_posts()) {
            $this->incrementAttempts($email);
            return new WP_Error('invalid_auth', 'Credenciales incorrectas', ['status' => 401]);
        }

        $user_post   = $query->posts[0];
        $stored_hash = get_post_meta($user_post->ID, '_ch_user_pass_hash', true);
        $status      = get_post_meta($user_post->ID, '_ch_user_status', true);

        if ($status === 'banned') {
            return new WP_Error('account_banned', 'Esta cuenta ha sido suspendida.', ['status' => 403]);
        }

        if ($status === 'pending') {
            return new WP_Error('account_pending', 'Esta cuenta aún no ha sido activada.', ['status' => 403]);
        }

        if (!$stored_hash || !wp_check_password($password, $stored_hash)) {
            $this->incrementAttempts($email);

            $attempts_data    = $this->getAttempts($email);
            $remaining        = self::$max_attempts - $attempts_data['count'];
            $remaining_msg    = $remaining > 0
                ? " Te quedan {$remaining} intento(s) antes del bloqueo temporal."
                : ' Tu cuenta ha sido bloqueada temporalmente.';

            return new WP_Error('invalid_auth', 'Credenciales incorrectas.' . $remaining_msg, ['status' => 401]);
        }

        $this->clearAttempts($email);

        $role = get_post_meta($user_post->ID, '_ch_user_role', true) ?: 'user';

        $payload = [
            'iss'  => get_bloginfo('url'),
            'iat'  => time(),
            'exp'  => time() + (60 * 60 * 24 * 7),
            'data' => [
                'user_id' => $user_post->ID,
                'email'   => $email,
                'role'    => $role,
            ],
        ];

        $token = JWT::encode($payload, ChileHalal_API_Middleware::get_jwt_secret(), 'HS256');

        return new WP_REST_Response([
            'success' => true,
            'data'    => [
                'id'    => $user_post->ID,
                'name'  => $user_post->post_title,
                'role'  => $role,
                'token' => $token,
            ],
        ], 200);
    }
}