<?php

if (!defined('ABSPATH'))
    exit;

use Firebase\JWT\JWT;

class ChileHalal_API_Routes
{

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        register_rest_route('chilehalal/v1', '/products', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_get_products'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('chilehalal/v1', '/products', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_create_product'],
            'permission_callback' => [$this, 'check_auth_middleware'],
        ]);

        register_rest_route('chilehalal/v1', '/products/(?P<id>\d+)', [
            [
                'methods' => 'PUT',
                'callback' => [$this, 'handle_update_product'],
                'permission_callback' => [$this, 'check_auth_middleware'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'handle_delete_product'],
                'permission_callback' => [$this, 'check_auth_middleware'],
            ]
        ]);

        register_rest_route('chilehalal/v1', '/scan/(?P<barcode>[a-zA-Z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_scan'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('chilehalal/v1', '/categories', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_get_categories'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('chilehalal/v1', '/brands', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_get_brands'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('chilehalal/v1', '/auth/register', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_register'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('chilehalal/v1', '/auth/login', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_login'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('chilehalal/v1', '/user/me', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_get_me'],
            'permission_callback' => [$this, 'check_auth_middleware'],
        ]);

        register_rest_route('chilehalal/v1', '/user/update', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_update_user'],
            'permission_callback' => [$this, 'check_auth_middleware'],
        ]);

        register_rest_route('chilehalal/v1', '/favorites', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_get_favorites'],
            'permission_callback' => [$this, 'check_auth_middleware'],
        ]);

        register_rest_route('chilehalal/v1', '/favorites/toggle', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_toggle_favorite'],
            'permission_callback' => [$this, 'check_auth_middleware'],
        ]);

        register_rest_route('chilehalal/v1', '/favorites/check/(?P<product_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_check_favorite'],
            'permission_callback' => [$this, 'check_auth_middleware'],
        ]);
    }

    public function check_auth_middleware($request)
    {
        $auth_result = ChileHalal_API_Middleware::validate_request($request);
        if (is_wp_error($auth_result)) {
            return $auth_result;
        }
        $request->set_param('auth_user', $auth_result);
        return true;
    }

    public function handle_get_products($request)
    {
        $page = $request->get_param('page') ?: 1;
        $search = $request->get_param('search');
        $category_id = $request->get_param('category_id');
        $brands_param = $request->get_param('brands');

        $args = [
            'post_type' => 'ch_product',
            'posts_per_page' => 16,
            'paged' => $page,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
            'meta_query' => []
        ];

        if (!empty($search)) {
            $args['s'] = sanitize_text_field($search);
        }

        if (!empty($category_id)) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'ch_product_category',
                    'field'    => 'term_id',
                    'terms'    => intval($category_id),
                ]
            ];
        }

        if (!empty($brands_param)) {
            $brands_array = array_map('sanitize_text_field', explode(',', $brands_param));
            $args['meta_query'][] = [
                'key'     => '_ch_brand',
                'value'   => $brands_array,
                'compare' => 'IN'
            ];
        }

        $query = new WP_Query($args);
        $products = [];

        if ($query->have_posts()) {
            foreach ($query->posts as $post) {
                $terms = get_the_terms($post->ID, 'ch_product_category');
                $categories = [];
                if ($terms && !is_wp_error($terms)) {
                    foreach ($terms as $term) {
                        $categories[] = $term->name;
                    }
                }

                $products[] = [
                    'id' => $post->ID,
                    'name' => $post->post_title,
                    'description' => wp_strip_all_tags(get_post_meta($post->ID, '_ch_description', true)),
                    'brand' => get_post_meta($post->ID, '_ch_brand', true),
                    'categories' => $categories,
                    'is_halal' => get_post_meta($post->ID, '_ch_is_halal', true) === 'yes',
                    'barcode' => get_post_meta($post->ID, '_ch_barcode', true),
                    'image_url' => get_the_post_thumbnail_url($post->ID, 'medium') ?: null
                ];
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $products,
            'pagination' => [
                'current_page' => (int) $page,
                'total_pages' => $query->max_num_pages,
                'total_items' => $query->found_posts
            ]
        ], 200);
    }

    public function handle_get_categories($request)
    {
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

    public function handle_get_brands($request)
    {
        global $wpdb;
        $brands = $wpdb->get_col("
            SELECT DISTINCT meta_value 
            FROM $wpdb->postmeta 
            WHERE meta_key = '_ch_brand' AND meta_value != ''
            ORDER BY meta_value ASC
        ");

        return new WP_REST_Response([
            'success' => true,
            'data' => $brands
        ], 200);
    }

    public function handle_create_product($request)
    {
        $user_data = $request->get_param('auth_user');
        $user_id = $user_data->user_id;
        $params = $request->get_json_params();

        if (empty($params['name']) || empty($params['brand'])) {
            return new WP_Error('missing_data', 'Nombre y Marca requeridos', ['status' => 400]);
        }

        $can_manage = ChileHalal_API_Middleware::check_permission(
            $user_id,
            'manage_products',
            ['brand' => $params['brand']]
        );

        if (!$can_manage) {
            return new WP_Error('forbidden', 'No tienes permisos para gestionar productos de esta marca.', ['status' => 403]);
        }

        $post_id = wp_insert_post([
            'post_type' => 'ch_product',
            'post_title' => sanitize_text_field($params['name']),
            'post_status' => 'publish',
            'post_author' => $user_id
        ]);

        if (is_wp_error($post_id)) return $post_id;

        update_post_meta($post_id, '_ch_brand', sanitize_text_field($params['brand']));
        update_post_meta($post_id, '_ch_barcode', sanitize_text_field($params['barcode'] ?? ''));
        update_post_meta($post_id, '_ch_is_halal', sanitize_text_field($params['is_halal'] ?? 'doubt'));
        update_post_meta($post_id, '_ch_description', sanitize_textarea_field($params['description'] ?? ''));

        if (!empty($params['image_base64'])) {
            $image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $params['image_base64']));
            if ($image_data !== false) {
                $filename = 'product_' . $post_id . '_' . time() . '.jpg';
                $upload_file = wp_upload_bits($filename, null, $image_data);
                
                if (!$upload_file['error']) {
                    $wp_filetype = wp_check_filetype($filename, null);
                    $attachment = [
                        'post_mime_type' => $wp_filetype['type'],
                        'post_title'     => sanitize_file_name($filename),
                        'post_content'   => '',
                        'post_status'    => 'inherit'
                    ];
                    $attachment_id = wp_insert_attachment($attachment, $upload_file['file'], $post_id);
                    if (!is_wp_error($attachment_id)) {
                        require_once(ABSPATH . 'wp-admin/includes/image.php');
                        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_file['file']);
                        wp_update_attachment_metadata($attachment_id, $attachment_data);
                        set_post_thumbnail($post_id, $attachment_id);
                    }
                }
            }
        }

        if (!empty($params['categories']) && is_array($params['categories'])) {
            $cat_ids = array_map('intval', $params['categories']);
            wp_set_object_terms($post_id, $cat_ids, 'ch_product_category');
        }

        return new WP_REST_Response([
            'success' => true,
            'id' => $post_id,
            'message' => 'Producto creado correctamente'
        ], 201);
    }

    public function handle_delete_product($request)
    {
        $user_data = $request->get_param('auth_user');
        $user_id = $user_data->user_id;
        $product_id = intval($request['id']);

        $post = get_post($product_id);
        if (!$post || $post->post_type !== 'ch_product') {
            return new WP_Error('not_found', 'Producto no encontrado', ['status' => 404]);
        }

        $product_brand = get_post_meta($product_id, '_ch_brand', true);

        $can_manage = ChileHalal_API_Middleware::check_permission(
            $user_id,
            'manage_products',
            ['brand' => $product_brand]
        );

        if (!$can_manage) {
            return new WP_Error('forbidden', 'No tienes permisos para eliminar este producto.', ['status' => 403]);
        }

        $result = wp_delete_post($product_id, true);

        if (!$result) {
            return new WP_Error('delete_failed', 'Error al eliminar el producto', ['status' => 500]);
        }

        return new WP_REST_Response([
            'success' => true, 
            'message' => 'Producto eliminado correctamente'
        ], 200);
    }

    public function handle_update_product($request)
    {
        $user_data = $request->get_param('auth_user');
        $user_id = $user_data->user_id;
        $product_id = intval($request['id']);
        $params = $request->get_json_params();

        $post = get_post($product_id);
        if (!$post || $post->post_type !== 'ch_product') {
            return new WP_Error('not_found', 'Producto no encontrado', ['status' => 404]);
        }

        $current_brand = get_post_meta($product_id, '_ch_brand', true);

        $can_manage = ChileHalal_API_Middleware::check_permission(
            $user_id,
            'manage_products',
            ['brand' => $current_brand]
        );

        if (!$can_manage) {
            return new WP_Error('forbidden', 'No tienes permisos para editar este producto.', ['status' => 403]);
        }

        if (!empty($params['brand']) && $params['brand'] !== $current_brand) {
            $can_manage_new_brand = ChileHalal_API_Middleware::check_permission(
                $user_id,
                'manage_products',
                ['brand' => $params['brand']]
            );
            if (!$can_manage_new_brand) {
                return new WP_Error('forbidden', 'No tienes permisos para asignar esta nueva marca.', ['status' => 403]);
            }
            update_post_meta($product_id, '_ch_brand', sanitize_text_field($params['brand']));
        }

        if (!empty($params['name'])) {
            wp_update_post([
                'ID' => $product_id,
                'post_title' => sanitize_text_field($params['name'])
            ]);
        }

        if (isset($params['barcode'])) update_post_meta($product_id, '_ch_barcode', sanitize_text_field($params['barcode']));
        if (isset($params['is_halal'])) update_post_meta($product_id, '_ch_is_halal', sanitize_text_field($params['is_halal']));
        if (isset($params['description'])) update_post_meta($product_id, '_ch_description', sanitize_textarea_field($params['description']));

        if (isset($params['categories']) && is_array($params['categories'])) {
            $cat_ids = array_map('intval', $params['categories']);
            wp_set_object_terms($product_id, $cat_ids, 'ch_product_category');
        }

        if (!empty($params['image_base64'])) {
            $image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $params['image_base64']));
            if ($image_data !== false) {
                $filename = 'product_' . $product_id . '_' . time() . '.jpg';
                $upload_file = wp_upload_bits($filename, null, $image_data);
                
                if (!$upload_file['error']) {
                    $wp_filetype = wp_check_filetype($filename, null);
                    $attachment = [
                        'post_mime_type' => $wp_filetype['type'],
                        'post_title'     => sanitize_file_name($filename),
                        'post_content'   => '',
                        'post_status'    => 'inherit'
                    ];
                    $attachment_id = wp_insert_attachment($attachment, $upload_file['file'], $product_id);
                    if (!is_wp_error($attachment_id)) {
                        require_once(ABSPATH . 'wp-admin/includes/image.php');
                        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_file['file']);
                        wp_update_attachment_metadata($attachment_id, $attachment_data);
                        set_post_thumbnail($product_id, $attachment_id);
                    }
                }
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Producto actualizado correctamente'
        ], 200);
    }

    public function handle_scan($request)
    {
        $barcode = sanitize_text_field($request['barcode']);
        $args = [
            'post_type' => 'ch_product',
            'posts_per_page' => 1,
            'meta_key' => '_ch_barcode',
            'meta_value' => $barcode,
            'post_status' => 'publish'
        ];
        $query = new WP_Query($args);

        if ($query->have_posts()) {
            $post = $query->posts[0];
            $terms = get_the_terms($post->ID, 'ch_product_category');
            $categories = [];
            if ($terms && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $categories[] = $term->name;
                }
            }

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'id' => $post->ID,
                    'name' => $post->post_title,
                    'description' => wp_strip_all_tags(get_post_meta($post->ID, '_ch_description', true)),
                    'brand' => get_post_meta($post->ID, '_ch_brand', true),
                    'categories' => $categories,
                    'is_halal' => get_post_meta($post->ID, '_ch_is_halal', true) === 'yes',
                    'barcode' => get_post_meta($post->ID, '_ch_barcode', true),
                    'image_url' => get_the_post_thumbnail_url($post->ID, 'medium') ?: null
                ]
            ], 200);
        }
        return new WP_REST_Response(['success' => false, 'message' => 'Producto no encontrado'], 404);
    }

    public function handle_register($request)
    {
        $params = $request->get_json_params();
        $email = sanitize_email($params['email'] ?? '');
        $password = $params['password'] ?? '';
        $name = sanitize_text_field($params['name'] ?? '');

        if (empty($email) || empty($password) || empty($name))
            return new WP_Error('missing_fields', 'Faltan datos', ['status' => 400]);
        if (!is_email($email))
            return new WP_Error('invalid_email', 'Correo inválido', ['status' => 400]);

        $existing = new WP_Query([
            'post_type' => 'ch_app_user',
            'meta_key' => '_ch_user_email',
            'meta_value' => $email,
            'posts_per_page' => 1,
            'post_status' => 'any'
        ]);
        if ($existing->have_posts())
            return new WP_Error('user_exists', 'Correo ya registrado', ['status' => 409]);

        $post_id = wp_insert_post(['post_title' => $name, 'post_type' => 'ch_app_user', 'post_status' => 'publish']);
        if (is_wp_error($post_id))
            return new WP_Error('db_error', 'Error al crear usuario', ['status' => 500]);

        update_post_meta($post_id, '_ch_user_email', $email);
        update_post_meta($post_id, '_ch_user_status', 'active');
        update_post_meta($post_id, '_ch_user_role', 'user');
        update_post_meta($post_id, '_ch_user_pass_hash', wp_hash_password($password));

        return new WP_REST_Response(['success' => true, 'message' => 'Usuario registrado'], 201);
    }

    public function handle_login($request)
    {
        $params = $request->get_json_params();
        $email = sanitize_email($params['email'] ?? '');
        $password = $params['password'] ?? '';

        if (empty($email) || empty($password))
            return new WP_Error('missing_fields', 'Datos requeridos', ['status' => 400]);

        $query = new WP_Query([
            'post_type' => 'ch_app_user',
            'meta_key' => '_ch_user_email',
            'meta_value' => $email,
            'posts_per_page' => 1
        ]);
        if (!$query->have_posts())
            return new WP_Error('invalid_auth', 'Credenciales incorrectas', ['status' => 401]);

        $user_post = $query->posts[0];
        $stored_hash = get_post_meta($user_post->ID, '_ch_user_pass_hash', true);

        if ($stored_hash && wp_check_password($password, $stored_hash)) {
            $role = get_post_meta($user_post->ID, '_ch_user_role', true) ?: 'user';
            $payload = [
                'iss' => get_bloginfo('url'),
                'iat' => time(),
                'exp' => time() + (60 * 60 * 24 * 7),
                'data' => ['user_id' => $user_post->ID, 'email' => $email, 'role' => $role]
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

    public function handle_get_me($request)
    {
        $auth_user = $request->get_param('auth_user'); 
        $user_id = $auth_user->user_id;
        
        $post = get_post($user_id);
        if (!$post) return new WP_Error('not_found', 'Usuario no encontrado', ['status' => 404]);

        $profile_image_url = get_the_post_thumbnail_url($user_id, 'thumbnail') ?: null;
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
                'profile_image' => $profile_image_url
            ]
        ], 200);
    }

    public function handle_update_user($request)
    {
        $auth_user = $request->get_param('auth_user');
        $user_id = $auth_user->user_id;
        $params = $request->get_json_params();

        if (!empty($params['name'])) {
            wp_update_post([
                'ID' => $user_id,
                'post_title' => sanitize_text_field($params['name'])
            ]);
        }

        if (isset($params['phone'])) {
            update_post_meta($user_id, '_ch_user_phone', sanitize_text_field($params['phone']));
        }
        
        if (isset($params['company'])) {
            update_post_meta($user_id, '_ch_user_company', sanitize_text_field($params['company']));
        }

        if (!empty($params['image_base64'])) {
            $image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $params['image_base64']));
            if ($image_data !== false) {
                $filename = 'user_' . $user_id . '_' . time() . '.jpg';
                $upload_file = wp_upload_bits($filename, null, $image_data);
                
                if (!$upload_file['error']) {
                    $wp_filetype = wp_check_filetype($filename, null);
                    $attachment = [
                        'post_mime_type' => $wp_filetype['type'],
                        'post_title'     => sanitize_file_name($filename),
                        'post_content'   => '',
                        'post_status'    => 'inherit'
                    ];
                    $attachment_id = wp_insert_attachment($attachment, $upload_file['file'], $user_id);
                    if (!is_wp_error($attachment_id)) {
                        require_once(ABSPATH . 'wp-admin/includes/image.php');
                        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_file['file']);
                        wp_update_attachment_metadata($attachment_id, $attachment_data);
                        set_post_thumbnail($user_id, $attachment_id);
                    }
                }
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Perfil actualizado correctamente'
        ], 200);
    }

    public function handle_get_favorites($request)
    {
        $auth_user = $request->get_param('auth_user');
        $user_id = $auth_user->user_id;

        $favorites = get_post_meta($user_id, '_ch_user_favorites', true);
        if (!is_array($favorites)) $favorites = [];

        if (empty($favorites)) {
            return new WP_REST_Response(['success' => true, 'data' => []], 200);
        }

        $args = [
            'post_type'      => 'ch_product',
            'post__in'       => $favorites,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'post__in'
        ];

        $query = new WP_Query($args);
        $products = [];

        if ($query->have_posts()) {
            foreach ($query->posts as $post) {
                $terms = get_the_terms($post->ID, 'ch_product_category');
                $categories = [];
                if ($terms && !is_wp_error($terms)) {
                    foreach ($terms as $term) {
                        $categories[] = $term->name;
                    }
                }

                $products[] = [
                    'id' => $post->ID,
                    'name' => $post->post_title,
                    'description' => wp_strip_all_tags(get_post_meta($post->ID, '_ch_description', true)),
                    'brand' => get_post_meta($post->ID, '_ch_brand', true),
                    'categories' => $categories,
                    'is_halal' => get_post_meta($post->ID, '_ch_is_halal', true) === 'yes',
                    'barcode' => get_post_meta($post->ID, '_ch_barcode', true),
                    'image_url' => get_the_post_thumbnail_url($post->ID, 'medium') ?: null
                ];
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $products
        ], 200);
    }

    public function handle_toggle_favorite($request)
    {
        $auth_user = $request->get_param('auth_user');
        $user_id = $auth_user->user_id;
        $params = $request->get_json_params();

        if (empty($params['product_id'])) {
            return new WP_Error('missing_data', 'ID de producto requerido', ['status' => 400]);
        }

        $product_id = intval($params['product_id']);
        $favorites = get_post_meta($user_id, '_ch_user_favorites', true);
        if (!is_array($favorites)) $favorites = [];

        $is_favorite = false;
        $index = array_search($product_id, $favorites);

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

    public function handle_check_favorite($request)
    {
        $auth_user = $request->get_param('auth_user');
        $user_id = $auth_user->user_id;
        $product_id = intval($request['product_id']);

        $favorites = get_post_meta($user_id, '_ch_user_favorites', true);
        if (!is_array($favorites)) $favorites = [];

        return new WP_REST_Response([
            'success' => true,
            'is_favorite' => in_array($product_id, $favorites)
        ], 200);
    }
}