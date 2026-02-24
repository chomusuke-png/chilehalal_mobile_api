<?php
// templates/admin/dashboard.php
// Variables disponibles: $product_count, $user_count
?>
<div class="wrap">
    <h1>Panel de Control ChileHalal Mobile</h1>
    <p>Bienvenido al centro de mando de la API. Desde aquí controlas los datos que se sirven a la App Flutter.</p>

    <div style="display: flex; gap: 20px; margin-top: 20px;">
        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-left: 4px solid #325cad; width: 300px;">
            <h2 style="margin-top: 0;">Productos Escaneables</h2>
            <p style="font-size: 2em; font-weight: bold; margin: 10px 0;">
                <?php echo esc_html( $product_count ); ?>
            </p>
            <p><a href="<?php echo admin_url('edit.php?post_type=ch_product'); ?>" class="button button-primary">Gestionar Productos</a></p>
        </div>

        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-left: 4px solid #e40318; width: 300px;">
            <h2 style="margin-top: 0;">Usuarios Registrados</h2>
            <p style="font-size: 2em; font-weight: bold; margin: 10px 0;">
                <?php echo esc_html( $user_count ); ?>
            </p>
            <p><a href="<?php echo admin_url('edit.php?post_type=ch_app_user'); ?>" class="button button-primary">Gestionar Usuarios</a></p>
        </div>
    </div>
</div>