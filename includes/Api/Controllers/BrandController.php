<?php
if (!defined('ABSPATH')) exit;

class ChileHalal_Brand_Controller {
    public function getBrands($request) {
        $brands = get_transient('ch_all_brands');

        if (false === $brands) {
            global $wpdb;
            $brands = $wpdb->get_col("
                SELECT DISTINCT meta_value 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = '_ch_brand' AND meta_value != ''
                ORDER BY meta_value ASC
            ");
            
            set_transient('ch_all_brands', $brands, 12 * HOUR_IN_SECONDS);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $brands
        ], 200);
    }
}