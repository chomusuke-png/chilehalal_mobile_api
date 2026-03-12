<?php
if (!defined('ABSPATH')) exit;

wp_nonce_field('save_ch_coupon', 'ch_coupon_nonce');
?>

<style>
    .ch-coupon-row { margin-bottom: 20px; }
    .ch-coupon-row label { font-weight: 600; display: block; margin-bottom: 6px; }
    .ch-coupon-row input[type="text"], 
    .ch-coupon-row input[type="date"], 
    .ch-coupon-row select { width: 100%; max-width: 500px; }
    .ch-helper { font-size: 12px; color: #646970; margin-top: 4px; display: block; }
</style>

<div class="ch-coupon-row">
    <label for="ch_coupon_business_id">Negocio Asociado</label>
    <select id="ch_coupon_business_id" name="ch_coupon_business_id" required>
        <option value="">-- Seleccionar Negocio --</option>
        <?php foreach ($businesses as $biz): ?>
            <option value="<?php echo esc_attr($biz->ID); ?>" <?php selected($business_id, $biz->ID); ?>>
                <?php echo esc_html($biz->post_title); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <span class="ch-helper">El cupón aparecerá dentro de la ficha de este negocio en la app.</span>
</div>

<div class="ch-coupon-row">
    <label for="ch_coupon_discount">Oferta / Beneficio</label>
    <input type="text" id="ch_coupon_discount" name="ch_coupon_discount" value="<?php echo esc_attr($discount); ?>" placeholder="Ej: 15% de descuento en el total" required>
</div>

<div class="ch-coupon-row">
    <label for="ch_coupon_code">Código Único de Canje</label>
    <input type="text" id="ch_coupon_code" name="ch_coupon_code" value="<?php echo esc_attr($code); ?>" placeholder="Ej: HALAL15" style="text-transform: uppercase;">
    <span class="ch-helper">El código que el usuario debe mostrar en caja.</span>
</div>

<div class="ch-coupon-row">
    <label for="ch_coupon_expiry">Fecha de Expiración</label>
    <input type="date" id="ch_coupon_expiry" name="ch_coupon_expiry" value="<?php echo esc_attr($expiry); ?>">
</div>

<div class="ch-coupon-row">
    <label for="ch_coupon_status">Estado</label>
    <select id="ch_coupon_status" name="ch_coupon_status">
        <option value="active" <?php selected($status, 'active'); ?>>🟢 Activo</option>
        <option value="expired" <?php selected($status, 'expired'); ?>>🔴 Expirado / Inactivo</option>
    </select>
</div>