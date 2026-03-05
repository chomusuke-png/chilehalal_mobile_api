<?php
if (!defined('ABSPATH')) exit;
?>

<style>
    .ch-dashboard-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
    .ch-stat-card { background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
    .ch-stat-card.blue-accent { border-left: 4px solid #2271b1; }
    .ch-stat-card.red-accent { border-left: 4px solid #d63638; }
    .ch-stat-card h2 { margin: 0 0 10px 0; font-size: 16px; color: #1d2327; }
    .ch-stat-number { font-size: 32px; font-weight: 600; margin: 0 0 15px 0; color: #1d2327; }
</style>

<div class="wrap">
    <h1 class="wp-heading-inline">Panel de Control ChileHalal Mobile</h1>
    <p>Bienvenido al centro de mando de la API. Desde aquí controlas los datos que se sirven a la App Flutter.</p>

    <div class="ch-dashboard-grid">
        <div class="ch-stat-card blue-accent">
            <h2>Productos Escaneables</h2>
            <p class="ch-stat-number"><?php echo esc_html($product_count); ?></p>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=ch_product')); ?>" class="button button-primary">Gestionar Productos</a>
        </div>

        <div class="ch-stat-card red-accent">
            <h2>Usuarios Registrados</h2>
            <p class="ch-stat-number"><?php echo esc_html($user_count); ?></p>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=ch_app_user')); ?>" class="button button-primary">Gestionar Usuarios</a>
        </div>
    </div>
</div>