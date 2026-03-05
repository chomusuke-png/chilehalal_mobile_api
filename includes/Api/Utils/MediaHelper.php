<?php
if (!defined('ABSPATH')) exit;

class ChileHalal_Media_Helper {
    public static function uploadBase64Image($base64_string, $post_id, $prefix = 'image') {
        $image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64_string));
        
        if ($image_data === false) {
            return new WP_Error('invalid_image', 'Los datos de la imagen son inválidos.', ['status' => 400]);
        }

        $size_in_mb = strlen($image_data) / (1024 * 1024);
        if ($size_in_mb > 5) {
            return new WP_Error('file_too_large', 'La imagen excede el límite de 5MB.', ['status' => 413]);
        }

        $filename = $prefix . '_' . $post_id . '_' . time() . '.jpg';
        $upload_file = wp_upload_bits($filename, null, $image_data);
        
        if ($upload_file['error']) {
            return new WP_Error('upload_error', $upload_file['error'], ['status' => 500]);
        }

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

        return $attachment_id;
    }
}