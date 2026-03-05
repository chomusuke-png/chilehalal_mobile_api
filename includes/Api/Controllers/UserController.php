<?php
if (!defined('ABSPATH')) exit;

class ChileHalal_User_Controller {
    public function getMe($request) {
        $user_id = $request->get_param('auth_user')->user_id;
        $post = get_post($user_id);
        
        if (!$post) {
            return new WP_Error('not_found', 'Usuario no encontrado', ['status' => 404]);
        }

        $brands = get_post_meta($user_id, '_ch_user_brands', true);

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'id' => $user_id,
                'name' => $post->post_title,
                'email' => get_post_meta($user_id, '_ch_user_email', true),
                'role' => get_post_meta($user_id, '_ch_user_role', true) ?: 'user',
                'status' => get_post_meta($user_id, '_ch_user_status', true),
                'brands' => is_array($brands) ? $brands : [],
                'phone' => get_post_meta($user_id, '_ch_user_phone', true),
                'company' => get_post_meta($user_id, '_ch_user_company', true),
                'profile_image' => get_the_post_thumbnail_url($user_id, 'thumbnail') ?: null
            ]
        ], 200);
    }

    public function updateProfile($request) {
        $user_id = $request->get_param('auth_user')->user_id;
        $params = $request->get_json_params();

        if (!empty($params['name'])) {
            wp_update_post([
                'ID' => $user_id,
                'post_title' => sanitize_text_field($params['name'])
            ]);
        }

        if (isset($params['phone'])) update_post_meta($user_id, '_ch_user_phone', sanitize_text_field($params['phone']));
        if (isset($params['company'])) update_post_meta($user_id, '_ch_user_company', sanitize_text_field($params['company']));

        if (!empty($params['image_base64'])) {
            $upload_result = ChileHalal_Media_Helper::uploadBase64Image($params['image_base64'], $user_id, 'user');
            if (is_wp_error($upload_result)) {
                return $upload_result;
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Perfil actualizado correctamente'
        ], 200);
    }

    public function getFavorites($request) {
        $user_id = $request->get_param('auth_user')->user_id;
        $favorites = get_post_meta($user_id, '_ch_user_favorites', true) ?: [];

        if (empty($favorites)) {
            return new WP_REST_Response(['success' => true, 'data' => []], 200);
        }

        $query = new WP_Query([
            'post_type'      => 'ch_product',
            'post__in'       => $favorites,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'post__in'
        ]);

        $products = [];
        if ($query->have_posts()) {
            $product_controller = new ChileHalal_Product_Controller();
            $reflection = new ReflectionMethod($product_controller, 'formatProductResponse');
            $reflection->setAccessible(true);
            
            foreach ($query->posts as $post) {
                $products[] = $reflection->invoke($product_controller, $post);
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $products
        ], 200);
    }

    public function toggleFavorite($request) {
        $user_id = $request->get_param('auth_user')->user_id;
        $params = $request->get_json_params();

        if (empty($params['product_id'])) {
            return new WP_Error('missing_data', 'ID de producto requerido', ['status' => 400]);
        }

        $product_id = intval($params['product_id']);
        $favorites = get_post_meta($user_id, '_ch_user_favorites', true) ?: [];
        $index = array_search($product_id, $favorites);
        $is_favorite = false;

        if ($index !== false) {
            unset($favorites[$index]);
            $favorites = array_values($favorites);
        } else {
            $favorites[] = $product_id;
            $is_favorite = true;
        }

        update_post_meta($user_id, '_ch_user_favorites', $favorites);

        return new WP_REST_Response([
            'success' => true,
            'is_favorite' => $is_favorite
        ], 200);
    }

    public function checkFavorite($request) {
        $user_id = $request->get_param('auth_user')->user_id;
        $product_id = intval($request['product_id']);
        $favorites = get_post_meta($user_id, '_ch_user_favorites', true) ?: [];

        return new WP_REST_Response([
            'success' => true,
            'is_favorite' => in_array($product_id, $favorites)
        ], 200);
    }
}