<?php
// templates/metaboxes/user-meta.php
// Variables disponibles: $email, $phone, $status, $role
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

    <div>
        <p>
            <label><strong>Estado de la Cuenta</strong></label><br>
            <select name="ch_user_status" style="width:100%; padding: 8px;">
                <option value="active" <?php selected($status, 'active'); ?>>🟢 Activo</option>
                <option value="banned" <?php selected($status, 'banned'); ?>>🔴 Bloqueado</option>
                <option value="pending" <?php selected($status, 'pending'); ?>>🟡 Pendiente</option>
            </select>
        </p>
        
        <p>
            <label><strong>Rol en la App</strong></label><br>
            <select name="ch_user_role" style="width:100%; padding: 8px; border: 1px solid #999;">
                <option value="user" <?php selected($role, 'user'); ?>>Usuario (Normal)</option>
                <option value="editor" <?php selected($role, 'editor'); ?>>Editor (Puede modificar)</option>
                <option value="owner" <?php selected($role, 'owner'); ?>>Owner (Super Admin)</option>
            </select>
            <br>
        </p>

        <!-- borrar en un futuro -->
        <p style="margin-top: 20px; border-top: 1px solid #ddd; padding-top: 10px;">
            <label style="color: #d63638;"><strong>Establecer Nueva Contraseña:</strong></label><br>
            <input type="text" name="ch_user_new_pass" value="" placeholder="Escribe aquí para cambiar..." style="width:100%; padding: 8px;">
            <br><small>Si se deja vacío, no cambiará la contraseña actual.</small>
        </p>
    </div>
</div>