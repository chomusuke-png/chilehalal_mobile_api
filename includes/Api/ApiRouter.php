<?php
if (!defined('ABSPATH')) exit;

class ChileHalal_Api_Router {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        $product_controller = new ChileHalal_Product_Controller();
        $category_controller = new ChileHalal_Category_Controller();
        $brand_controller = new ChileHalal_Brand_Controller();
        $auth_controller = new ChileHalal_Auth_Controller();
        $user_controller = new ChileHalal_User_Controller();
        
        $middleware = new ChileHalal_Jwt_Auth_Middleware();
        $auth_callback = [$middleware, 'checkAuth'];

        register_rest_route('chilehalal/v1', '/products', [
            [
                'methods' => 'GET',
                'callback' => [$product_controller, 'getProducts'],
                'permission_callback' => '__return_true',
            ],
            [
                'methods' => 'POST',
                'callback' => [$product_controller, 'createProduct'],
                'permission_callback' => $auth_callback,
            ]
        ]);

        register_rest_route('chilehalal/v1', '/products/(?P<id>\d+)', [
            [
                'methods' => 'PUT',
                'callback' => [$product_controller, 'updateProduct'],
                'permission_callback' => $auth_callback,
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$product_controller, 'deleteProduct'],
                'permission_callback' => $auth_callback,
            ]
        ]);

        register_rest_route('chilehalal/v1', '/scan/(?P<barcode>[a-zA-Z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$product_controller, 'scanProduct'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('chilehalal/v1', '/categories', [
            'methods' => 'GET',
            'callback' => [$category_controller, 'getCategories'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('chilehalal/v1', '/brands', [
            'methods' => 'GET',
            'callback' => [$brand_controller, 'getBrands'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('chilehalal/v1', '/auth/register', [
            'methods' => 'POST',
            'callback' => [$auth_controller, 'register'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('chilehalal/v1', '/auth/login', [
            'methods' => 'POST',
            'callback' => [$auth_controller, 'login'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('chilehalal/v1', '/user/me', [
            'methods' => 'GET',
            'callback' => [$user_controller, 'getMe'],
            'permission_callback' => $auth_callback,
        ]);

        register_rest_route('chilehalal/v1', '/user/update', [
            'methods' => 'POST',
            'callback' => [$user_controller, 'updateProfile'],
            'permission_callback' => $auth_callback,
        ]);

        register_rest_route('chilehalal/v1', '/favorites', [
            'methods' => 'GET',
            'callback' => [$user_controller, 'getFavorites'],
            'permission_callback' => $auth_callback,
        ]);

        register_rest_route('chilehalal/v1', '/favorites/toggle', [
            'methods' => 'POST',
            'callback' => [$user_controller, 'toggleFavorite'],
            'permission_callback' => $auth_callback,
        ]);

        register_rest_route('chilehalal/v1', '/favorites/check/(?P<product_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$user_controller, 'checkFavorite'],
            'permission_callback' => $auth_callback,
        ]);
    }
}