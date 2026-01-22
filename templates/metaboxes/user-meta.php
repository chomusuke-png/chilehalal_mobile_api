<?php
// templates/metaboxes/user-meta.php
// Variables: $email, $phone, $status, $role, $company, $brands (array)
$brands_str = is_array($brands) ? implode(', ', $brands) : '';
?>

<?php wp_nonce_field('save_ch_user', 'ch_user_nonce'); ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.querySelector('select[name="ch_user_role"]');
        const partnerFields = document.getElementById('partner-fields');

        function togglePartnerFields() {
            if (roleSelect.value === 'partner') {
                partnerFields.style.display = 'block';
            } else {
                partnerFields.style.display = 'none';
            }
        }
        roleSelect.addEventListener('change', togglePartnerFields);
        togglePartnerFields(); // Init
    });
</script>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
    <div>
        <p>
            <label><strong>Correo Electrónico</strong></label><br>
            <input type="email" name="ch_user_email" value="<?php echo esc_attr($email); ?>"
                style="width:100%; padding: 8px;">
        </p>
        <p>
            <label><strong>Teléfono</strong></label><br>
            <input type="text" name="ch_user_phone" value="<?php echo esc_attr($phone); ?>"
                style="width:100%; padding: 8px;">
        </p>
    </div>

    <div>
        <p>
            <label><strong>Rol en la App</strong></label><br>
            <select name="ch_user_role" style="width:100%; padding: 8px; font-weight: bold;">
                <option value="user" <?php selected($role, 'user'); ?>>Usuario (Lectura)</option>
                <option value="partner" <?php selected($role, 'partner'); ?>>Partner (Gestión Marcas Propias)</option>
                <option value="editor" <?php selected($role, 'editor'); ?>>Editor (Gestión Global)</option>
                <option value="owner" <?php selected($role, 'owner'); ?>>Owner (Super Admin)</option>
            </select>
        </p>

        <div id="partner-fields"
            style="background: #f0f6fc; padding: 15px; border: 1px solid #cce5ff; border-radius: 5px; margin-bottom: 15px; display: none;">
            <p>
                <label><strong>Empresa / Razón Social</strong></label><br>
                <input type="text" name="ch_user_company" value="<?php echo esc_attr($company); ?>"
                    placeholder="Ej: Importadora Ltda." style="width:100%;">
            </p>
            <p>
                <label><strong>Marcas Autorizadas</strong> (Separadas por coma)</label><br>
                <input type="text" name="ch_user_brands" value="<?php echo esc_attr($brands_str); ?>"
                    placeholder="Ej: Nestlé, Savory, Costa" style="width:100%;">
                <br><small style="color: #666;">El usuario solo podrá crear/editar productos de estas marcas
                    exactas.</small>
            </p>
        </div>

        <p>
            <label><strong>Estado</strong></label><br>
            <select name="ch_user_status" style="width:100%; padding: 8px;">
                <option value="active" <?php selected($status, 'active'); ?>>🟢 Activo</option>
                <option value="banned" <?php selected($status, 'banned'); ?>>🔴 Bloqueado</option>
                <option value="pending" <?php selected($status, 'pending'); ?>>🟡 Pendiente</option>
            </select>
        </p>

        <p style="margin-top: 20px; border-top: 1px solid #ddd; padding-top: 10px;">
            <label style="color: #d63638;"><strong>Cambiar Contraseña:</strong></label><br>
            <input type="text" name="ch_user_new_pass" placeholder="Escribir nueva..."
                style="width:100%; padding: 8px;">
        </p>
    </div>
</div>