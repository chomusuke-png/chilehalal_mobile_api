<?php
if (!defined('ABSPATH')) exit;

class ChileHalal_Business_Controller {
    
    public function getBusinesses($request) {
        $page = $request->get_param('page') ?: 1;
        $search = $request->get_param('search');
        $type = $request->get_param('type');

        $args = [
            'post_type' => 'ch_business',
            'posts_per_page' => 15,
            'paged' => $page,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
            'meta_query' => []
        ];

        if (!empty($search)) {
            $args['s'] = sanitize_text_field($search);
        }

        if (!empty($type)) {
            $args['meta_query'][] = [
                'key' => '_ch_business_type',
                'value' => sanitize_text_field($type),
                'compare' => 'LIKE'
            ];
        }

        $query = new WP_Query($args);
        $businesses = [];

        if ($query->have_posts()) {
            foreach ($query->posts as $post) {
                $businesses[] = $this->formatBusinessResponse($post, false); // false = no traemos el menú completo en la lista
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $businesses,
            'pagination' => [
                'current_page' => (int) $page,
                'total_pages' => $query->max_num_pages,
                'total_items' => $query->found_posts
            ]
        ], 200);
    }

    public function getBusinessSingle($request) {
        $id = (int) $request->get_param('id');
        $post = get_post($id);

        if (!$post || $post->post_type !== 'ch_business' || $post->post_status !== 'publish') {
            return new WP_Error('not_found', 'Negocio no encontrado', ['status' => 404]);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $this->formatBusinessResponse($post, true)
        ], 200);
    }

    private function formatBusinessResponse($post, $include_details = true) {
        $menu_json = get_post_meta($post->ID, '_ch_business_menu', true);
        $menu = json_decode($menu_json, true) ?: [];

        $has_halal = false;
        $has_non_halal = false;
        
        foreach ($menu as $cat) {
            if (!empty($cat['items']) && is_array($cat['items'])) {
                foreach ($cat['items'] as $item) {
                    if (isset($item['is_halal']) && ($item['is_halal'] === true || $item['is_halal'] === 'true')) {
                        $has_halal = true;
                    } else {
                        $has_non_halal = true;
                    }
                }
            }
        }

        $computed_status = 'none';
        if ($has_halal && !$has_non_halal) $computed_status = 'full';
        elseif ($has_halal && $has_non_halal) $computed_status = 'partial';

        $response = [
            'id' => $post->ID,
            'name' => $post->post_title,
            'type' => get_post_meta($post->ID, '_ch_business_type', true),
            'address' => get_post_meta($post->ID, '_ch_business_address', true),
            'phone' => get_post_meta($post->ID, '_ch_business_phone', true),
            'computed_halal_status' => $computed_status,
            'thumbnail_url' => get_the_post_thumbnail_url($post->ID, 'medium') ?: null,
        ];

        if ($include_details) {
            $gallery_ids = get_post_meta($post->ID, '_ch_business_gallery', true) ?: [];
            $gallery_urls = [];
            foreach ($gallery_ids as $img_id) {
                $url = wp_get_attachment_image_url($img_id, 'large');
                if ($url) $gallery_urls[] = $url;
            }

            $response['gallery_urls'] = $gallery_urls;
            $response['menu'] = $menu;
        }

        return $response;
    }
}