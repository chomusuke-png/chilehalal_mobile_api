<?php
if (!defined('ABSPATH')) exit;

class ChileHalal_Audit_Logger {
    
    public static function log($userId, $action, $resourceType, $resourceId, $details = []) {
        $postTitle = sprintf('[%s] %s %s (#%d)', date('Y-m-d H:i:s'), strtoupper($action), ucfirst($resourceType), $resourceId);
        
        $logId = wp_insert_post([
            'post_type'   => 'ch_audit_log',
            'post_title'  => sanitize_text_field($postTitle),
            'post_status' => 'publish',
            'post_author' => $userId,
        ]);

        if (!is_wp_error($logId)) {
            update_post_meta($logId, '_ch_audit_action', sanitize_text_field($action));
            update_post_meta($logId, '_ch_audit_resource_type', sanitize_text_field($resourceType));
            update_post_meta($logId, '_ch_audit_resource_id', intval($resourceId));
            update_post_meta($logId, '_ch_audit_details', wp_json_encode($details));
            update_post_meta($logId, '_ch_audit_ip', self::getClientIp());
        }
    }

    private static function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        }
        return sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }
}