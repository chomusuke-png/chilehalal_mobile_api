<?php
// templates/metaboxes/product-meta.php
// Variables disponibles: $barcode, $is_halal, $brand, $description
?>
<style>
    .ch-row { margin-bottom: 15px; display: flex; flex-direction: column; border-bottom: 1px solid #eee; padding-bottom: 15px; }
    .ch-row:last-child { border-bottom: none; }
    .ch-row label { font-weight: bold; margin-bottom: 5px; font-size: 1.1em; }
    .ch-row input[type="text"], .ch-row textarea, .ch-row select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
    .ch-helper { font-size: 0.9em; color: #666; margin-top: 4px; }
</style>

<?php wp_nonce_field( 'save_ch_product', 'ch_product_nonce' ); ?>

<div class="ch-row">
    <label for="ch_barcode">Código de Barras</label>
    <input type="text" id="ch_barcode" name="ch_barcode" value="<?php echo esc_attr($barcode); ?>" placeholder="Ej: 780123456789">
    <div class="ch-helper">Escanea el producto o ingresa el número manualmente.</div>
</div>

<div class="ch-row">
    <label for="ch_is_halal">Estado de Certificación</label>
    <select name="ch_is_halal" id="ch_is_halal">
        <option value="yes" <?php selected($is_halal, 'yes'); ?>>✅ Certificado Halal</option>
        <option value="no" <?php selected($is_halal, 'no'); ?>>❌ Haram / No Certificado</option>
        <option value="doubt" <?php selected($is_halal, 'doubt'); ?>>⚠️ Dudoso (Mashbooh)</option>
    </select>
</div>

<div class="ch-row">
    <label for="ch_brand">Marca / Fabricante</label>
    <input type="text" id="ch_brand" name="ch_brand" value="<?php echo esc_attr($brand); ?>" placeholder="Ej: Nestlé, Costa, etc.">
</div>

<div class="ch-row">
    <label for="ch_description">Ingredientes / Detalles Técnicos</label>
    <textarea name="ch_description" id="ch_description" rows="5"><?php echo esc_textarea($description); ?></textarea>
    <div class="ch-helper">Información extra que verá el usuario en la App.</div>
</div>