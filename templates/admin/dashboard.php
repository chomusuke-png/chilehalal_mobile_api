<?php
if (!defined('ABSPATH')) exit;

// ── Datos para el dashboard ──────────────────────────────────────────────────

$products      = wp_count_posts('ch_product');
$product_count = $products->publish ?? 0;

$businesses      = wp_count_posts('ch_business');
$business_count  = $businesses->publish ?? 0;

$coupons      = wp_count_posts('ch_coupon');
$coupon_count = $coupons->publish ?? 0;

$users_query = wp_count_posts('ch_app_user');
$user_count  = ($users_query->publish ?? 0) + ($users_query->draft ?? 0) + ($users_query->private ?? 0);

// Últimos 5 productos
$latest_products = get_posts([
    'post_type'      => 'ch_product',
    'posts_per_page' => 5,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
]);

// Últimos 5 usuarios
$latest_users = get_posts([
    'post_type'      => 'ch_app_user',
    'posts_per_page' => 5,
    'post_status'    => 'any',
    'orderby'        => 'date',
    'order'          => 'DESC',
]);

// Últimas 8 acciones de auditoría
$latest_audit = get_posts([
    'post_type'      => 'ch_audit_log',
    'posts_per_page' => 8,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
]);

$action_colors = [
    'create' => ['bg' => '#dcfce7', 'color' => '#166534', 'label' => 'CREÓ'],
    'update' => ['bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'EDITÓ'],
    'delete' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'ELIMINÓ'],
];
?>

<style>
/* ── Reset y base ── */
.ch-dash * { box-sizing: border-box; }
.ch-dash {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    padding: 20px 20px 40px;
    max-width: 1400px;
}

/* ── Header ── */
.ch-dash-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 2px solid #e5e7eb;
}
.ch-dash-header-icon {
    width: 52px; height: 52px;
    background: linear-gradient(135deg, #2271b1, #135e96);
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 12px rgba(34,113,177,0.3);
}
.ch-dash-header-icon span { font-size: 24px; }
.ch-dash-header h1 { margin: 0; font-size: 26px; font-weight: 700; color: #111827; }
.ch-dash-header p  { margin: 4px 0 0; color: #6b7280; font-size: 14px; }

/* ── Grid de stats ── */
.ch-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 18px;
    margin-bottom: 32px;
}
.ch-stat-card {
    background: #fff;
    border-radius: 16px;
    padding: 22px 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    display: flex;
    align-items: center;
    gap: 18px;
    transition: transform .15s, box-shadow .15s;
    text-decoration: none;
}
.ch-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 20px rgba(0,0,0,.10);
}
.ch-stat-icon {
    width: 54px; height: 54px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}
.ch-stat-info { min-width: 0; }
.ch-stat-number {
    font-size: 32px;
    font-weight: 800;
    line-height: 1;
    color: #111827;
    margin: 0 0 4px;
}
.ch-stat-label {
    font-size: 13px;
    color: #6b7280;
    font-weight: 500;
    margin: 0;
}

/* Colores de cada stat */
.ch-stat-blue   .ch-stat-icon { background: #eff6ff; color: #2563eb; }
.ch-stat-green  .ch-stat-icon { background: #f0fdf4; color: #16a34a; }
.ch-stat-orange .ch-stat-icon { background: #fff7ed; color: #ea580c; }
.ch-stat-purple .ch-stat-icon { background: #faf5ff; color: #9333ea; }

/* ── Grid de contenido ── */
.ch-content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}
.ch-content-grid.ch-full { grid-template-columns: 1fr; }

@media (max-width: 900px) {
    .ch-content-grid { grid-template-columns: 1fr; }
}

/* ── Cards de contenido ── */
.ch-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    overflow: hidden;
}
.ch-card-header {
    padding: 18px 22px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.ch-card-title {
    font-size: 15px;
    font-weight: 700;
    color: #111827;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.ch-card-title span { font-size: 18px; }
.ch-card-link {
    font-size: 12px;
    color: #2271b1;
    text-decoration: none;
    font-weight: 500;
}
.ch-card-link:hover { text-decoration: underline; }

/* ── Listas de items ── */
.ch-item-list { list-style: none; margin: 0; padding: 0; }
.ch-item-list li {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 22px;
    border-bottom: 1px solid #f9fafb;
    transition: background .1s;
}
.ch-item-list li:last-child { border-bottom: none; }
.ch-item-list li:hover { background: #f9fafb; }

.ch-item-avatar {
    width: 36px; height: 36px;
    border-radius: 10px;
    overflow: hidden;
    flex-shrink: 0;
    background: #f3f4f6;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
}
.ch-item-avatar img { width: 100%; height: 100%; object-fit: cover; }

.ch-item-info { flex: 1; min-width: 0; }
.ch-item-name {
    font-size: 13px;
    font-weight: 600;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin: 0 0 2px;
}
.ch-item-meta {
    font-size: 11px;
    color: #9ca3af;
    margin: 0;
}

.ch-item-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 20px;
    flex-shrink: 0;
}

.ch-empty {
    padding: 32px 22px;
    text-align: center;
    color: #9ca3af;
    font-size: 13px;
}

/* ── Auditoría ── */
.ch-audit-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 22px;
    border-bottom: 1px solid #f9fafb;
}
.ch-audit-row:last-child { border-bottom: none; }
.ch-audit-row:hover { background: #f9fafb; }

.ch-audit-action-badge {
    font-size: 10px;
    font-weight: 800;
    padding: 3px 9px;
    border-radius: 20px;
    flex-shrink: 0;
    min-width: 60px;
    text-align: center;
    letter-spacing: .4px;
}
.ch-audit-desc {
    flex: 1;
    font-size: 12px;
    color: #374151;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ch-audit-time {
    font-size: 11px;
    color: #9ca3af;
    flex-shrink: 0;
}

/* ── Halal badges ── */
.badge-yes    { background: #dcfce7; color: #166534; }
.badge-no     { background: #fee2e2; color: #991b1b; }
.badge-doubt  { background: #fef9c3; color: #854d0e; }
.badge-user   { background: #eff6ff; color: #1d4ed8; }
.badge-partner{ background: #faf5ff; color: #7e22ce; }
.badge-editor { background: #fff7ed; color: #c2410c; }
.badge-owner  { background: #fdf2f8; color: #be185d; }
</style>

<div class="ch-dash">

    <!-- Header -->
    <div class="ch-dash-header">
        <div class="ch-dash-header-icon"><span>📱</span></div>
        <div>
            <h1>ChileHalal Mobile</h1>
            <p>Panel de control · <?php echo esc_html(date_i18n('l, j \d\e F \d\e Y')); ?></p>
        </div>
    </div>

    <!-- Stat cards -->
    <div class="ch-stats-grid">
        <a class="ch-stat-card ch-stat-blue" href="<?php echo esc_url(admin_url('edit.php?post_type=ch_product')); ?>">
            <div class="ch-stat-icon">📦</div>
            <div class="ch-stat-info">
                <p class="ch-stat-number"><?php echo esc_html($product_count); ?></p>
                <p class="ch-stat-label">Productos</p>
            </div>
        </a>

        <a class="ch-stat-card ch-stat-green" href="<?php echo esc_url(admin_url('edit.php?post_type=ch_app_user')); ?>">
            <div class="ch-stat-icon">👥</div>
            <div class="ch-stat-info">
                <p class="ch-stat-number"><?php echo esc_html($user_count); ?></p>
                <p class="ch-stat-label">Usuarios</p>
            </div>
        </a>

        <a class="ch-stat-card ch-stat-orange" href="<?php echo esc_url(admin_url('edit.php?post_type=ch_business')); ?>">
            <div class="ch-stat-icon">🏪</div>
            <div class="ch-stat-info">
                <p class="ch-stat-number"><?php echo esc_html($business_count); ?></p>
                <p class="ch-stat-label">Negocios</p>
            </div>
        </a>

        <a class="ch-stat-card ch-stat-purple" href="<?php echo esc_url(admin_url('edit.php?post_type=ch_coupon')); ?>">
            <div class="ch-stat-icon">🎟️</div>
            <div class="ch-stat-info">
                <p class="ch-stat-number"><?php echo esc_html($coupon_count); ?></p>
                <p class="ch-stat-label">Cupones</p>
            </div>
        </a>
    </div>

    <!-- Últimos productos y usuarios -->
    <div class="ch-content-grid">

        <!-- Últimos productos -->
        <div class="ch-card">
            <div class="ch-card-header">
                <h2 class="ch-card-title"><span>📦</span> Últimos Productos</h2>
                <a class="ch-card-link" href="<?php echo esc_url(admin_url('post-new.php?post_type=ch_product')); ?>">+ Nuevo</a>
            </div>
            <?php if (!empty($latest_products)) : ?>
                <ul class="ch-item-list">
                    <?php foreach ($latest_products as $p) :
                        $halal  = get_post_meta($p->ID, '_ch_is_halal', true);
                        $brand  = get_post_meta($p->ID, '_ch_brand', true);
                        $img    = get_the_post_thumbnail_url($p->ID, 'thumbnail');
                        $badges = ['yes' => ['✅ Halal', 'badge-yes'], 'no' => ['❌ Haram', 'badge-no'], 'doubt' => ['⚠️ Dudoso', 'badge-doubt']];
                        [$blabel, $bclass] = $badges[$halal] ?? ['–', 'badge-doubt'];
                    ?>
                        <li>
                            <div class="ch-item-avatar">
                                <?php if ($img) : ?>
                                    <img src="<?php echo esc_url($img); ?>" alt="">
                                <?php else : ?>
                                    📦
                                <?php endif; ?>
                            </div>
                            <div class="ch-item-info">
                                <p class="ch-item-name"><?php echo esc_html($p->post_title); ?></p>
                                <p class="ch-item-meta"><?php echo esc_html($brand ?: 'Sin marca'); ?> · <?php echo esc_html(human_time_diff(strtotime($p->post_date), time())); ?> ago</p>
                            </div>
                            <span class="ch-item-badge <?php echo esc_attr($bclass); ?>"><?php echo esc_html($blabel); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="ch-empty">No hay productos todavía.</p>
            <?php endif; ?>
        </div>

        <!-- Últimos usuarios -->
        <div class="ch-card">
            <div class="ch-card-header">
                <h2 class="ch-card-title"><span>👥</span> Últimos Usuarios</h2>
                <a class="ch-card-link" href="<?php echo esc_url(admin_url('post-new.php?post_type=ch_app_user')); ?>">+ Nuevo</a>
            </div>
            <?php if (!empty($latest_users)) : ?>
                <ul class="ch-item-list">
                    <?php foreach ($latest_users as $u) :
                        $email  = get_post_meta($u->ID, '_ch_user_email', true);
                        $role   = get_post_meta($u->ID, '_ch_user_role', true) ?: 'user';
                        $img    = get_the_post_thumbnail_url($u->ID, 'thumbnail');
                        $role_badges = [
                            'user'    => ['Usuario',  'badge-user'],
                            'partner' => ['Partner',  'badge-partner'],
                            'editor'  => ['Editor',   'badge-editor'],
                            'owner'   => ['Owner',    'badge-owner'],
                        ];
                        [$rlabel, $rclass] = $role_badges[$role] ?? ['Usuario', 'badge-user'];
                    ?>
                        <li>
                            <div class="ch-item-avatar">
                                <?php if ($img) : ?>
                                    <img src="<?php echo esc_url($img); ?>" alt="">
                                <?php else : ?>
                                    👤
                                <?php endif; ?>
                            </div>
                            <div class="ch-item-info">
                                <p class="ch-item-name"><?php echo esc_html($u->post_title); ?></p>
                                <p class="ch-item-meta"><?php echo esc_html($email ?: '–'); ?> · <?php echo esc_html(human_time_diff(strtotime($u->post_date), time())); ?> ago</p>
                            </div>
                            <span class="ch-item-badge <?php echo esc_attr($rclass); ?>"><?php echo esc_html($rlabel); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="ch-empty">No hay usuarios todavía.</p>
            <?php endif; ?>
        </div>

    </div>

    <!-- Auditoría -->
    <div class="ch-content-grid ch-full">
        <div class="ch-card">
            <div class="ch-card-header">
                <h2 class="ch-card-title"><span>🕵️</span> Últimas Acciones</h2>
                <a class="ch-card-link" href="<?php echo esc_url(admin_url('edit.php?post_type=ch_audit_log')); ?>">Ver todo</a>
            </div>
            <?php if (!empty($latest_audit)) : ?>
                <?php foreach ($latest_audit as $log) :
                    $action       = get_post_meta($log->ID, '_ch_audit_action', true);
                    $resource     = get_post_meta($log->ID, '_ch_audit_resource_type', true);
                    $resource_id  = get_post_meta($log->ID, '_ch_audit_resource_id', true);
                    $author_post  = get_post($log->post_author);
                    $author_name  = $author_post ? $author_post->post_title : 'Sistema';
                    $ac           = $action_colors[$action] ?? ['bg' => '#f3f4f6', 'color' => '#374151', 'label' => strtoupper($action)];
                ?>
                    <div class="ch-audit-row">
                        <span class="ch-audit-action-badge" style="background:<?php echo esc_attr($ac['bg']); ?>; color:<?php echo esc_attr($ac['color']); ?>">
                            <?php echo esc_html($ac['label']); ?>
                        </span>
                        <span class="ch-audit-desc">
                            <strong><?php echo esc_html($author_name); ?></strong>
                            <?php echo esc_html(strtolower($ac['label'])); ?>
                            un <?php echo esc_html($resource); ?>
                            <?php if ($resource_id) : ?>
                                <span style="color:#9ca3af;">(#<?php echo esc_html($resource_id); ?>)</span>
                            <?php endif; ?>
                        </span>
                        <span class="ch-audit-time"><?php echo esc_html(human_time_diff(strtotime($log->post_date), time())); ?> ago</span>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="ch-empty">No hay acciones registradas todavía.</p>
            <?php endif; ?>
        </div>
    </div>

</div>