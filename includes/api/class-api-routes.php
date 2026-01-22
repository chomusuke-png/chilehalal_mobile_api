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

    // --- RUTAS ---
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

        register_rest_route('chilehalal/v1', '/scan/(?P<barcode>[a-zA-Z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_scan'],
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
    }

    // --- MIDDLEWARE WRAPPER PARA WP REST API ---
    public function check_auth_middleware($request)
    {
        $auth_result = ChileHalal_API_Middleware::validate_request($request);

        if (is_wp_error($auth_result)) {
            return $auth_result;
        }

        $request->set_param('auth_user', $auth_result);
        return true;
    }

    // --- HANDLER MOSTRAR PRODUCTOS ---
    public function handle_get_products($request)
    {
        $page = $request->get_param('page') ?: 1;
        $search = $request->get_param('search');

        $args = [
            'post_type' => 'ch_product',
            'posts_per_page' => 16,
            'paged' => $page,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ];

        if (!empty($search)) {
            $args['s'] = sanitize_text_field($search);
        }

        $query = new WP_Query($args);
        $products = [];

        if ($query->have_posts()) {
            foreach ($query->posts as $post) {
                // Obtener categorias
                $terms = get_the_terms($post->ID, 'ch_product_category');
                $cats = [];
                if ($terms && !is_wp_error($terms)) {
                    foreach ($terms as $term) {
                        $cats[] = $term->name;
                    }
                }

                $products[] = [
                    'id' => $post->ID,
                    'name' => $post->post_title,
                    'description' => wp_strip_all_tags(get_post_meta($post->ID, '_ch_description', true)),
                    'brand' => get_post_meta($post->ID, '_ch_brand', true),
                    'categories' => $cats,
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

    // --- HANDLER CREAR PRODUCTO
    public function handle_create_product($request)
    {
        $user_data = $request->get_param('auth_user');
        $user_id = $user_data->user_id;

        $params = $request->get_json_params();

        // Validar datos mínimos
        if (empty($params['name']) || empty($params['brand'])) {
            return new WP_Error('missing_data', 'Nombre y Marca requeridos', ['status' => 400]);
        }

        // VALIDACIÓN DE SEGURIDAD (ACL)
        // Pasamos la marca intentada al checker para ver si el partner la posee
        $can_manage = ChileHalal_API_Middleware::check_permission(
            $user_id,
            'manage_products',
            ['brand' => $params['brand']]
        );

        if (!$can_manage) {
            return new WP_Error('forbidden', 'No tienes permisos para gestionar productos de esta marca.', ['status' => 403]);
        }

        // Crear Post
        $post_id = wp_insert_post([
            'post_type' => 'ch_product',
            'post_title' => sanitize_text_field($params['name']),
            'post_status' => 'publish',
            'post_author' => $user_id // Asignar al creador
        ]);

        if (is_wp_error($post_id))
            return $post_id;

        // Guardar Meta
        update_post_meta($post_id, '_ch_brand', sanitize_text_field($params['brand']));
        update_post_meta($post_id, '_ch_barcode', sanitize_text_field($params['barcode'] ?? ''));
        update_post_meta($post_id, '_ch_is_halal', sanitize_text_field($params['is_halal'] ?? 'doubt'));

        // Guardar Categorías (Taxonomía)
        if (!empty($params['categories']) && is_array($params['categories'])) {
            // Se asume que envían IDs o Nombres. Si son nombres, WP intentará emparejarlos.
            wp_set_object_terms($post_id, $params['categories'], 'ch_product_category');
        }

        return new WP_REST_Response([
            'success' => true,
            'id' => $post_id,
            'message' => 'Producto creado correctamente'
        ], 201);
    }

    // --- HANDLER DEL ESCÁNER ---
    public function handle_scan($request)
    {
        $barcode = $request['barcode'];
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
            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'name' => $post->post_title,
                    'description' => wp_strip_all_tags(get_post_meta($post->ID, '_ch_description', true)),
                    'brand' => get_post_meta($post->ID, '_ch_brand', true),
                    'is_halal' => get_post_meta($post->ID, '_ch_is_halal', true) === 'yes',
                    'image_url' => get_the_post_thumbnail_url($post->ID, 'medium') ?: null
                ]
            ], 200);
        }
        return new WP_REST_Response(['success' => false, 'message' => 'Producto no encontrado'], 404);
    }

    // --- HANDLER DE REGISTRO ---
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

    // --- HANDLER DE LOGIN ---
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
        // ... (Verificaciones de ban, pass_hash igual que antes) ...
        $stored_hash = get_post_meta($user_post->ID, '_ch_user_pass_hash', true);

        if ($stored_hash && wp_check_password($password, $stored_hash)) {
            // ...
            $role = get_post_meta($user_post->ID, '_ch_user_role', true) ?: 'user';
            $payload = [
                'iss' => get_bloginfo('url'),
                'iat' => time(),
                'exp' => time() + (60 * 60 * 24 * 7),
                'data' => ['user_id' => $user_post->ID, 'email' => $email, 'role' => $role]
            ];

            // USAMOS EL MIDDLEWARE PARA EL SECRETO
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

    // --- HANDLER DE USUARIO ---
    public function handle_get_me($request)
    {
        $auth_user = $request->get_param('auth_user'); // Extraer del middleware
        $user_id = $auth_user->user_id;
        
        $post = get_post($user_id);
        if (!$post) return new WP_Error('not_found', 'Usuario no encontrado', ['status' => 404]);

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'id' => $user_id,
                'name' => $post->post_title,
                'role' => get_post_meta($user_id, '_ch_user_role', true) ?: 'user',
                'status' => get_post_meta($user_id, '_ch_user_status', true),
                'brands' => get_post_meta($user_id, '_ch_user_brands', true) // Útil para el Partner
            ]
        ], 200);
    }
}