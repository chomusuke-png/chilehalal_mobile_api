<?php
if (!defined('ABSPATH')) exit;

use Firebase\JWT\JWT;

class ChileHalal_Notification_Controller {
    
    public function broadcast($request) {
        $user_id = $request->get_param('auth_user')->user_id;
        $role = get_post_meta($user_id, '_ch_user_role', true);
        
        if ($role !== 'owner') {
            return new WP_Error('unauthorized', 'No tienes permisos de administrador para enviar notificaciones masivas.', ['status' => 403]);
        }

        $params = $request->get_json_params();
        if (empty($params['title']) || empty($params['message'])) {
            return new WP_Error('missing_data', 'El título y el mensaje son obligatorios.', ['status' => 400]);
        }

        $title = sanitize_text_field($params['title']);
        $message = sanitize_textarea_field($params['message']);

        $fcm_result = $this->sendFCMMessage($title, $message);

        if (is_wp_error($fcm_result)) {
            return $fcm_result;
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Notificación enviada exitosamente a todos los dispositivos.'
        ], 200);
    }

    private function sendFCMMessage($title, $message) {
        $key_path = plugin_dir_path(dirname(__DIR__, 2)) . 'credentials/firebase_service_account.json';
        
        if (!file_exists($key_path)) {
            return new WP_Error('fcm_error', 'Archivo de credenciales de Firebase no configurado en el servidor.', ['status' => 500]);
        }

        $key_data = json_decode(file_get_contents($key_path), true);
        $project_id = $key_data['project_id'];

        $access_token = $this->getGoogleAccessToken($key_data);
        if (is_wp_error($access_token)) {
            return $access_token;
        }

        $fcm_url = "https://fcm.googleapis.com/v1/projects/{$project_id}/messages:send";
        
        $fcm_payload = [
            'message' => [
                'topic' => 'all_users',
                'notification' => [
                    'title' => $title,
                    'body' => $message,
                ],
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'inbox_channel_id'
                    ]
                ]
            ]
        ];

        $response = wp_remote_post($fcm_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode($fcm_payload),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('fcm_error', 'Error de red al conectar con Firebase: ' . $response->get_error_message(), ['status' => 500]);
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (wp_remote_retrieve_response_code($response) >= 400) {
            return new WP_Error('fcm_error', 'Rechazado por Firebase: ' . ($body['error']['message'] ?? 'Error desconocido'), ['status' => 500]);
        }

        return true;
    }

    private function getGoogleAccessToken($key_data) {
        $now = time();
        
        $jwt_payload = [
            'iss' => $key_data['client_email'],
            'sub' => $key_data['client_email'],
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging'
        ];

        try {
            $jwt = JWT::encode($jwt_payload, $key_data['private_key'], 'RS256');
        } catch (Exception $e) {
            return new WP_Error('jwt_error', 'Error al generar la firma criptográfica: ' . $e->getMessage(), ['status' => 500]);
        }

        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ],
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('oauth_error', 'No se pudo obtener el token OAuth2 de Google.', ['status' => 500]);
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['access_token'])) {
            return $body['access_token'];
        }

        return new WP_Error('oauth_error', 'Google no retornó un access_token válido.', ['status' => 500]);
    }
}