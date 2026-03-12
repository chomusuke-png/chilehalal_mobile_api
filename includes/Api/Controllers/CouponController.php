<?php
if (!defined('ABSPATH')) exit;

class ChileHalal_Coupon_Controller {
    
    public function getCoupons($request) {
        $business_id = $request->get_param('business_id');
        $only_active = $request->get_param('active') !== 'false';

        $args = [
            'post_type' => 'ch_coupon',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => []
        ];

        if (!empty($business_id)) {
            $args['meta_query'][] = [
                'key' => '_ch_coupon_business_id',
                'value' => (int) $business_id,
                'compare' => '='
            ];
        }

        if ($only_active) {
            $args['meta_query'][] = [
                'key' => '_ch_coupon_status',
                'value' => 'active',
                'compare' => '='
            ];
            
            $args['meta_query'][] = [
                'relation' => 'OR',
                [
                    'key' => '_ch_coupon_expiry',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                ],
                [
                    'key' => '_ch_coupon_expiry',
                    'value' => '',
                    'compare' => '='
                ]
            ];
        }

        $query = new WP_Query($args);
        $coupons = [];

        if ($query->have_posts()) {
            foreach ($query->posts as $post) {
                $biz_id = get_post_meta($post->ID, '_ch_coupon_business_id', true);
                $biz_name = $biz_id ? get_the_title($biz_id) : 'General';

                $coupons[] = [
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'business_id' => (int) $biz_id,
                    'business_name' => $biz_name,
                    'discount' => get_post_meta($post->ID, '_ch_coupon_discount', true),
                    'code' => get_post_meta($post->ID, '_ch_coupon_code', true),
                    'expiry' => get_post_meta($post->ID, '_ch_coupon_expiry', true),
                    'status' => get_post_meta($post->ID, '_ch_coupon_status', true),
                ];
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $coupons
        ], 200);
    }
}