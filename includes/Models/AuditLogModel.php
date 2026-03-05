<?php
if (!defined('ABSPATH')) exit;

class ChileHalal_Audit_Log_Model {

    public function __construct() {
        add_action('init', [$this, 'registerAuditLogCpt']);
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('admin_head', [$this, 'hideAddAndEditButtons']);
    }

    public function registerAuditLogCpt() {
        register_post_type('ch_audit_log', [
            'labels' => [
                'name' => 'Historial de Cambios',
                'singular_name' => 'Registro',
                'search_items' => 'Buscar Registro',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'chilehalal-app',
            'supports' => ['title'],
            'capability_type' => 'post',
            'capabilities' => [
                'create_posts' => 'do_not_allow', 
            ],
            'map_meta_cap' => true,
            'has_archive' => false,
        ]);
    }

    public function hideAddAndEditButtons() {
        global $typenow;
        if ($typenow === 'ch_audit_log') {
            echo '<style>.page-title-action { display: none; } .row-actions .edit { display: none; } .row-actions .inline { display: none; }</style>';
        }
    }

    public function addMetaBoxes() {
        add_meta_box(
            'ch_audit_details', 
            'Detalles del Evento', 
            [$this, 'renderForm'], 
            'ch_audit_log', 
            'normal', 
            'high'
        );
    }

    public function renderForm($post) {
        $action = get_post_meta($post->ID, '_ch_audit_action', true);
        $resourceType = get_post_meta($post->ID, '_ch_audit_resource_type', true);
        $resourceId = get_post_meta($post->ID, '_ch_audit_resource_id', true);
        $ip = get_post_meta($post->ID, '_ch_audit_ip', true);
        $detailsJson = get_post_meta($post->ID, '_ch_audit_details', true);
        
        $details = json_decode($detailsJson, true);
        $author = get_user_by('id', $post->post_author);
        $authorName = $author ? $author->display_name . ' (' . $author->user_email . ')' : 'Sistema / Desconocido';

        ?>
        <div style="background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; font-family: monospace;">
            <p><strong style="color: #569cd6;">Usuario:</strong> <?php echo esc_html($authorName); ?></p>
            <p><strong style="color: #569cd6;">Acción:</strong> <?php echo esc_html(strtoupper($action)); ?></p>
            <p><strong style="color: #569cd6;">Recurso:</strong> <?php echo esc_html($resourceType); ?> (ID: <?php echo esc_html($resourceId); ?>)</p>
            <p><strong style="color: #569cd6;">IP Origen:</strong> <?php echo esc_html($ip); ?></p>
            <hr style="border-color: #333;">
            <p><strong style="color: #ce9178;">Cambios / Payload:</strong></p>
            <pre style="background: #000; padding: 10px; color: #4ec9b0; overflow-x: auto;"><?php echo esc_html(print_r($details, true)); ?></pre>
        </div>
        <?php
    }
}