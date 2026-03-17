<?php
if (!defined('ABSPATH')) exit;

class ChileHalal_Media_Helper {

    private static $allowed_mime_types = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    public static function uploadBase64Image($base64_string, $post_id, $prefix = 'image') {

        $base64_clean = preg_replace('#^data:image/\w+;base64,#i', '', $base64_string);
        $image_data = base64_decode($base64_clean, true);

        if ($image_data === false || empty($image_data)) {
            return new WP_Error('invalid_image', 'Los datos de la imagen son inválidos.', ['status' => 400]);
        }

        $size_in_mb = strlen($image_data) / (1024 * 1024);
        if ($size_in_mb > 5) {
            return new WP_Error('file_too_large', 'La imagen excede el límite de 5MB.', ['status' => 413]);
        }

        $real_mime = self::detectMimeType($image_data);

        if ($real_mime === null || !array_key_exists($real_mime, self::$allowed_mime_types)) {
            return new WP_Error(
                'invalid_mime_type',
                'Tipo de archivo no permitido. Solo se aceptan imágenes JPEG, PNG, WebP o GIF.',
                ['status' => 415]
            );
        }

        $extension = self::$allowed_mime_types[$real_mime];
        $filename = $prefix . '_' . $post_id . '_' . time() . '.' . $extension;

        $upload_file = wp_upload_bits($filename, null, $image_data);

        if ($upload_file['error']) {
            return new WP_Error('upload_error', $upload_file['error'], ['status' => 500]);
        }

        $wp_check = wp_check_filetype_and_ext($upload_file['file'], $filename);

        if (empty($wp_check['type']) || !in_array($wp_check['type'], array_keys(self::$allowed_mime_types))) {
            @unlink($upload_file['file']);
            return new WP_Error('invalid_file', 'El archivo no pasó la verificación de seguridad.', ['status' => 415]);
        }

        $attachment = [
            'post_mime_type' => $real_mime,
            'post_title'     => sanitize_file_name($filename),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];

        $attachment_id = wp_insert_attachment($attachment, $upload_file['file'], $post_id);

        if (is_wp_error($attachment_id)) {
            @unlink($upload_file['file']);
            return $attachment_id;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_file['file']);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        set_post_thumbnail($post_id, $attachment_id);

        return $attachment_id;
    }

    private static function detectMimeType($binary_data) {
        $header = substr($binary_data, 0, 12);
        $bytes  = array_values(unpack('C*', $header));

        // JPEG
        if ($bytes[0] === 0xFF && $bytes[1] === 0xD8 && $bytes[2] === 0xFF) {
            return 'image/jpeg';
        }

        // PNG
        if (
            $bytes[0] === 0x89 && $bytes[1] === 0x50 &&
            $bytes[2] === 0x4E && $bytes[3] === 0x47 &&
            $bytes[4] === 0x0D && $bytes[5] === 0x0A &&
            $bytes[6] === 0x1A && $bytes[7] === 0x0A
        ) {
            return 'image/png';
        }

        // GIF
        if ($bytes[0] === 0x47 && $bytes[1] === 0x49 && $bytes[2] === 0x46 && $bytes[3] === 0x38) {
            return 'image/gif';
        }

        // WebP
        if (
            $bytes[0] === 0x52 && $bytes[1] === 0x49 &&
            $bytes[2] === 0x46 && $bytes[3] === 0x46 &&
            $bytes[8] === 0x57 && $bytes[9] === 0x45 &&
            $bytes[10] === 0x42 && $bytes[11] === 0x50
        ) {
            return 'image/webp';
        }

        // Fallback
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_buffer($finfo, $binary_data);
            finfo_close($finfo);

            if (array_key_exists($mime, self::$allowed_mime_types)) {
                return $mime;
            }
        }

        return null;
    }
}