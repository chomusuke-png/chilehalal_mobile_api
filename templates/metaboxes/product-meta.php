<?php
if (!defined('ABSPATH')) exit;

wp_nonce_field('save_ch_product', 'ch_product_nonce');
?>

<style>
    .ch-product-row { margin-bottom: 20px; }
    .ch-product-row label { font-weight: 600; display: block; margin-bottom: 6px; }
    .ch-product-row input[type="text"], 
    .ch-product-row select, 
    .ch-product-row textarea { width: 100%; max-width: 100%; }
    .ch-product-helper { font-size: 12px; color: #646970; margin-top: 4px; display: block; }
</style>

<div class="ch-product-row">
    <label for="ch_barcode">Código de Barras</label>
    <input type="text" id="ch_barcode" name="ch_barcode" value="<?php echo esc_attr($barcode); ?>" placeholder="Ej: 780123456789">
    <span class="ch-product-helper">Escanea el producto o ingresa el número manualmente.</span>
</div>

<div class="ch-product-row">
    <label for="ch_is_halal">Estado de Certificación</label>
    <select name="ch_is_halal" id="ch_is_halal">
        <option value="yes" <?php selected($is_halal, 'yes'); ?>>✅ Certificado Halal</option>
        <option value="no" <?php selected($is_halal, 'no'); ?>>❌ Haram / No Certificado</option>
        <option value="doubt" <?php selected($is_halal, 'doubt'); ?>>⚠️ Dudoso (Mashbooh)</option>
    </select>
</div>

<div class="ch-product-row">
    <label for="ch_brand">Marca</label>
    <input type="text" id="ch_brand" name="ch_brand" value="<?php echo esc_attr($brand); ?>" placeholder="Ej: Nestlé, Costa, etc.">
</div>

<div class="ch-product-row">
    <label for="ch_description">Ingredientes / Detalles Técnicos</label>
    <textarea name="ch_description" id="ch_description" rows="5"><?php echo esc_textarea($description); ?></textarea>
    <span class="ch-product-helper">Información extra que verá el usuario en la App.</span>
</div>