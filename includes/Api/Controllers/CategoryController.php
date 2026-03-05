<?php
if (!defined('ABSPATH')) exit;

class ChileHalal_Category_Controller {
    public function getCategories($request) {
        $terms = get_terms([
            'taxonomy'   => 'ch_product_category',
            'hide_empty' => false,
        ]);

        $categories = [];
        
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term) {
                $image_id = get_term_meta($term->term_id, '_ch_category_image', true);
                $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : null;

                $categories[] = [
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'count' => $term->count,
                    'image_url' => $image_url
                ];
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $categories
        ], 200);
    }
}