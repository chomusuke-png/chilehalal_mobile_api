<?php
// templates/metaboxes/user-meta.php
?>
<?php wp_nonce_field( 'save_ch_user', 'ch_user_nonce' ); ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
    <div>
        <p>
            <label><strong>Correo Electrónico</strong></label><br>
            <input type="email" name="ch_user_email" value="<?php echo esc_attr($email); ?>" style="width:100%; padding: 8px;">
        </p>
        <p>
            <label><strong>Teléfono</strong></label><br>
            <input type="text" name="ch_user_phone" value="<?php echo esc_attr($phone); ?>" style="width:100%; padding: 8px;">
        </p>
    </div>

    <div style="background: #f9f9f9; padding: 15px; border-radius: 8px;">
        <p>
            <label><strong>Estado de la Cuenta</strong></label><br>
            <select name="ch_user_status" style="width:100%; padding: 8px;">
                <option value="active" <?php selected($status, 'active'); ?>>🟢 Activo</option>
                <option value="banned" <?php selected($status, 'banned'); ?>>🔴 Bloqueado</option>
                <option value="pending" <?php selected($status, 'pending'); ?>>🟡 Pendiente</option>
            </select>
        </p>
        <p>
            <label><strong>Nivel de Usuario</strong></label><br>
            <input type="number" name="ch_user_points" value="<?php echo esc_attr($points); ?>" style="width:100%; padding: 8px;">
        </p>
    </div>
</div>