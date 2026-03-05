<?php
if (!defined('ABSPATH')) exit;

$brands_str = is_array($brands) ? implode(', ', $brands) : '';
wp_nonce_field('save_ch_user', 'ch_user_nonce');
?>

<style>
    .ch-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
    .ch-meta-group { margin-bottom: 15px; }
    .ch-meta-group label { font-weight: 600; display: block; margin-bottom: 5px; }
    .ch-meta-group input[type="text"], 
    .ch-meta-group input[type="email"], 
    .ch-meta-group input[type="password"], 
    .ch-meta-group select { width: 100%; max-width: 400px; }
    .ch-partner-box { background: #f0f6fc; padding: 15px; border: 1px solid #cce5ff; border-radius: 4px; margin-bottom: 15px; display: none; }
    .ch-danger-zone { margin-top: 20px; border-top: 1px solid #dca0a0; padding-top: 15px; }
    .ch-danger-zone label { color: #d63638; }
    .ch-helper-text { font-size: 12px; color: #646970; display: block; margin-top: 4px; }
</style>

<div class="ch-meta-grid">
    <div>
        <div class="ch-meta-group">
            <label for="ch_user_email">Correo Electrónico</label>
            <input type="email" id="ch_user_email" name="ch_user_email" value="<?php echo esc_attr($email); ?>" required>
        </div>
        <div class="ch-meta-group">
            <label for="ch_user_phone">Teléfono</label>
            <input type="text" id="ch_user_phone" name="ch_user_phone" value="<?php echo esc_attr($phone); ?>">
        </div>
    </div>

    <div>
        <div class="ch-meta-group">
            <label for="ch_user_role">Rol en la App</label>
            <select id="ch_user_role" name="ch_user_role">
                <option value="user" <?php selected($role, 'user'); ?>>Usuario (Lectura)</option>
                <option value="partner" <?php selected($role, 'partner'); ?>>Partner (Gestión Marcas Propias)</option>
                <option value="editor" <?php selected($role, 'editor'); ?>>Editor (Gestión Global)</option>
                <option value="owner" <?php selected($role, 'owner'); ?>>Owner (Super Admin)</option>
            </select>
        </div>

        <div id="ch_partner_fields" class="ch-partner-box">
            <div class="ch-meta-group">
                <label for="ch_user_company">Empresa / Razón Social</label>
                <input type="text" id="ch_user_company" name="ch_user_company" value="<?php echo esc_attr($company); ?>" placeholder="Ej: Importadora Ltda.">
            </div>
            <div class="ch-meta-group">
                <label for="ch_user_brands">Marcas Autorizadas (Separadas por coma)</label>
                <input type="text" id="ch_user_brands" name="ch_user_brands" value="<?php echo esc_attr($brands_str); ?>" placeholder="Ej: Nestlé, Savory, Costa">
                <span class="ch-helper-text">El usuario solo podrá crear/editar productos de estas marcas exactas.</span>
            </div>
        </div>

        <div class="ch-meta-group">
            <label for="ch_user_status">Estado</label>
            <select id="ch_user_status" name="ch_user_status">
                <option value="active" <?php selected($status, 'active'); ?>>🟢 Activo</option>
                <option value="banned" <?php selected($status, 'banned'); ?>>🔴 Bloqueado</option>
                <option value="pending" <?php selected($status, 'pending'); ?>>🟡 Pendiente</option>
            </select>
        </div>

        <div class="ch-meta-group ch-danger-zone">
            <label for="ch_user_new_pass">Cambiar Contraseña</label>
            <input type="password" id="ch_user_new_pass" name="ch_user_new_pass" placeholder="Dejar en blanco para no cambiar" autocomplete="new-password">
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.getElementById('ch_user_role');
        const partnerFields = document.getElementById('ch_partner_fields');

        function togglePartnerVisibility() {
            if (roleSelect && partnerFields) {
                partnerFields.style.display = roleSelect.value === 'partner' ? 'block' : 'none';
            }
        }
        
        if (roleSelect) {
            roleSelect.addEventListener('change', togglePartnerVisibility);
            togglePartnerVisibility();
        }
    });
</script>