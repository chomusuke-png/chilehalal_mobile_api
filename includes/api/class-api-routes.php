<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class ChileHalal_API_Routes {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        // GET https://www.chilehalal.cl/wp-json/chilehalal/v1/scan/{codigo}
        register_rest_route( 'chilehalal/v1', '/scan/(?P<barcode>[a-zA-Z0-9-]+)', [
            'methods'  => 'GET',
            'callback' => [ $this, 'handle_scan' ],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handle_scan( $request ) {
        $barcode = $request['barcode'];

        // Consulta eficiente a la BD de WordPress
        $args = [
            'post_type'      => 'ch_product',
            'posts_per_page' => 1,
            'meta_key'       => '_ch_barcode',
            'meta_value'     => $barcode,
            'post_status'    => 'publish'
        ];

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            $post = $query->posts[0];
            $is_halal = get_post_meta( $post->ID, '_ch_is_halal', true );
            
            // Estructura JSON limpia para Flutter
            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'id'          => $post->ID,
                    'name'        => $post->post_title,
                    'description' => wp_strip_all_tags( $post->post_content ),
                    'is_halal'    => ($is_halal === 'yes'),
                    'image_url'   => get_the_post_thumbnail_url( $post->ID, 'medium' ) ?: null,
                    'last_update' => $post->post_modified
                ]
            ], 200);
        }

        return new WP_REST_Response([
            'success' => false,
            'message' => 'Producto no encontrado en la base de datos.'
        ], 404);
    }
}