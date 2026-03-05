<?php
if (!defined('ABSPATH')) exit;

class ChileHalal_Product_Controller {
    public function getProducts($request) {
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
                $products[] = $this->formatProductResponse($post);
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

    public function createProduct($request) {
        $user_id = $request->get_param('auth_user')->user_id;
        $params = $request->get_json_params();

        if (empty($params['name']) || empty($params['brand'])) {
            return new WP_Error('missing_data', 'Nombre y Marca requeridos', ['status' => 400]);
        }

        if (!ChileHalal_API_Middleware::check_permission($user_id, 'manage_products', ['brand' => $params['brand']])) {
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
            $upload_result = ChileHalal_Media_Helper::uploadBase64Image($params['image_base64'], $post_id, 'product');
            if (is_wp_error($upload_result)) {
                return $upload_result;
            }
        }

        if (!empty($params['categories']) && is_array($params['categories'])) {
            $cat_ids = array_map('intval', $params['categories']);
            wp_set_object_terms($post_id, $cat_ids, 'ch_product_category');
        }

        delete_transient('ch_all_brands');

        unset($params['image_base64']);
        ChileHalal_Audit_Logger::log($user_id, 'create', 'product', $post_id, $params);

        return new WP_REST_Response([
            'success' => true,
            'id' => $post_id,
            'message' => 'Producto creado correctamente'
        ], 201);
    }

    public function updateProduct($request) {
        $user_id = $request->get_param('auth_user')->user_id;
        $product_id = intval($request['id']);
        $params = $request->get_json_params();

        $post = get_post($product_id);
        if (!$post || $post->post_type !== 'ch_product') {
            return new WP_Error('not_found', 'Producto no encontrado', ['status' => 404]);
        }

        $current_brand = get_post_meta($product_id, '_ch_brand', true);

        if (!ChileHalal_API_Middleware::check_permission($user_id, 'manage_products', ['brand' => $current_brand])) {
            return new WP_Error('forbidden', 'No tienes permisos para editar este producto.', ['status' => 403]);
        }

        if (!empty($params['brand']) && $params['brand'] !== $current_brand) {
            if (!ChileHalal_API_Middleware::check_permission($user_id, 'manage_products', ['brand' => $params['brand']])) {
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
            $upload_result = ChileHalal_Media_Helper::uploadBase64Image($params['image_base64'], $product_id, 'product');
            if (is_wp_error($upload_result)) {
                return $upload_result;
            }
        }

        delete_transient('ch_all_brands');

        unset($params['image_base64']);
        ChileHalal_Audit_Logger::log($user_id, 'update', 'product', $product_id, $params);

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Producto actualizado correctamente'
        ], 200);
    }

    public function deleteProduct($request) {
        $user_id = $request->get_param('auth_user')->user_id;
        $product_id = intval($request['id']);

        $post = get_post($product_id);
        if (!$post || $post->post_type !== 'ch_product' || $post->post_status === 'trash') {
            return new WP_Error('not_found', 'Producto no encontrado o ya eliminado', ['status' => 404]);
        }

        $product_brand = get_post_meta($product_id, '_ch_brand', true);

        if (!ChileHalal_API_Middleware::check_permission($user_id, 'manage_products', ['brand' => $product_brand])) {
            return new WP_Error('forbidden', 'No tienes permisos para eliminar este producto.', ['status' => 403]);
        }

        if (!wp_trash_post($product_id)) {
            return new WP_Error('delete_failed', 'Error al enviar el producto a la papelera', ['status' => 500]);
        }

        delete_transient('ch_all_brands');

        ChileHalal_Audit_Logger::log($user_id, 'delete', 'product', $product_id, ['deleted_brand' => $product_brand]);

        return new WP_REST_Response([
            'success' => true, 
            'message' => 'Producto movido a la papelera correctamente'
        ], 200);
    }

    public function scanProduct($request) {
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
            return new WP_REST_Response([
                'success' => true,
                'data' => $this->formatProductResponse($query->posts[0])
            ], 200);
        }
        
        return new WP_REST_Response(['success' => false, 'message' => 'Producto no encontrado'], 404);
    }

    private function formatProductResponse($post) {
        $terms = get_the_terms($post->ID, 'ch_product_category');
        $categories = [];
        
        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $categories[] = $term->name;
            }
        }

        return [
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