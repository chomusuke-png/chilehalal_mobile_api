<?php
if (!defined('ABSPATH')) exit;

wp_nonce_field('save_ch_business', 'ch_business_nonce');

$gallery_val = is_array($gallery_ids) ? implode(',', $gallery_ids) : '';
$menu_json_val = empty($menu_json) ? '[]' : $menu_json;
?>

<style>
    /* Estilos Generales y Reseteo de Cajas */
    .ch-row { margin-bottom: 20px; box-sizing: border-box; }
    .ch-row label { font-weight: 600; display: block; margin-bottom: 6px; }
    .ch-row input[type="text"], .ch-row select { width: 100%; max-width: 500px; box-sizing: border-box; }
    
    /* Estilos de la Galería */
    .ch-gallery-container { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 10px; }
    .ch-gallery-item { position: relative; border: 1px solid #ddd; border-radius: 4px; padding: 2px; }
    .ch-gallery-item img { width: 100px; height: 100px; object-fit: cover; display: block; }
    .ch-gallery-item .remove-image { position: absolute; top: -8px; right: -8px; background: #d63638; color: #fff; border: none; border-radius: 50%; width: 20px; height: 20px; line-height: 18px; text-align: center; cursor: pointer; font-size: 14px; }
    
    /* --- Estilos del Constructor de Menú (Responsive) --- */
    .ch-menu-builder { background: #f8f9fa; border: 1px solid #ccd0d4; padding: 15px; border-radius: 5px; box-sizing: border-box; width: 100%; overflow: hidden; }
    .ch-category-card { background: #fff; border: 1px solid #ddd; margin-bottom: 15px; padding: 15px; border-radius: 4px; border-left: 4px solid #2271b1; box-sizing: border-box; }
    
    /* Cabecera de Categoría: Flexbox que permite salto de línea */
    .ch-category-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
    .ch-category-header input { flex: 1 1 200px; font-weight: bold; min-width: 0; box-sizing: border-box; }
    
    /* Tarjeta de Plato: Mucho padding-top para que el botón eliminar flote sin tapar los inputs */
    .ch-item-card { background: #fafafa; border: 1px dashed #ccc; padding: 35px 15px 15px 15px; margin-bottom: 10px; border-radius: 4px; position: relative; box-sizing: border-box; }
    
    /* Rejilla de Inputs: Flexbox Dinámico */
    .ch-item-grid { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; width: 100%; }
    .ch-item-grid input[type="text"] { flex: 1 1 150px; min-width: 0; box-sizing: border-box; }
    .ch-item-grid input[type="number"] { flex: 0 1 100px; min-width: 0; box-sizing: border-box; }
    .ch-item-grid label { flex: 0 0 auto; white-space: nowrap; margin: 0; font-size: 13px; display: flex; align-items: center; gap: 5px; }
    
    .ch-remove-btn { color: #d63638; cursor: pointer; text-decoration: underline; font-size: 12px; white-space: nowrap; }
</style>

<div class="ch-row">
    <label for="ch_business_type">Categoría / Tipo de Negocio</label>
    <input type="text" id="ch_business_type" name="ch_business_type" value="<?php echo esc_attr($type); ?>" placeholder="Ej: Restaurante, Carnicería, Minimarket">
</div>

<div class="ch-row">
    <label for="ch_business_address">Dirección Física</label>
    <input type="text" id="ch_business_address" name="ch_business_address" value="<?php echo esc_attr($address); ?>" placeholder="Ej: Av. Providencia 1234, Santiago">
</div>

<div class="ch-row">
    <label>Coordenadas GPS</label>
    <div style="display:flex; gap:10px; max-width:500px;">
        <input type="text" name="ch_business_latitude" value="<?php echo esc_attr($latitude); ?>" placeholder="Latitud (Ej: -33.4489)">
        <input type="text" name="ch_business_longitude" value="<?php echo esc_attr($longitude); ?>" placeholder="Longitud (Ej: -70.6693)">
    </div>
    <span style="font-size:12px;color:#646970;">Puedes obtenerlas desde Google Maps haciendo clic derecho en el local.</span>
</div>

<div class="ch-row">
    <label for="ch_business_phone">Teléfono / WhatsApp</label>
    <input type="text" id="ch_business_phone" name="ch_business_phone" value="<?php echo esc_attr($phone); ?>" placeholder="Ej: +56912345678">
</div>

<hr>

<div class="ch-row">
    <label>Galería de Fotos</label>
    <div class="ch-gallery-container" id="ch-gallery-container">
        <?php foreach ($gallery_ids as $id): ?>
            <div class="ch-gallery-item" data-id="<?php echo esc_attr($id); ?>">
                <?php echo wp_get_attachment_image($id, 'thumbnail'); ?>
                <button type="button" class="remove-image">&times;</button>
            </div>
        <?php endforeach; ?>
    </div>
    <input type="hidden" id="ch_business_gallery" name="ch_business_gallery" value="<?php echo esc_attr($gallery_val); ?>">
    <button type="button" class="button button-secondary" id="ch_add_gallery_btn">Añadir Fotos</button>
</div>

<hr>

<div class="ch-row">
    <label>Constructor de Menú Dinámico</label>
    <p class="description">Agrega categorías y los platos correspondientes. Marca la casilla verde si el plato es Halal.</p>
    
    <div class="ch-menu-builder" id="ch-menu-builder">
        <div id="ch-categories-container"></div>
        <button type="button" class="button button-primary" id="ch_add_category_btn" style="margin-top: 10px;">+ Añadir Categoría de Menú</button>
    </div>
    
    <input type="hidden" id="ch_business_menu" name="ch_business_menu" value="<?php echo esc_attr($menu_json_val); ?>">
</div>

<script>
jQuery(document).ready(function($){
    // --- LÓGICA DE LA GALERÍA ---
    var frame;
    $('#ch_add_gallery_btn').on('click', function(e) {
        e.preventDefault();
        if (frame) { frame.open(); return; }
        frame = wp.media({ title: 'Seleccionar fotos', button: { text: 'Añadir a galería' }, multiple: true });
        frame.on('select', function() {
            var attachment = frame.state().get('selection').toJSON();
            var idsInput = $('#ch_business_gallery');
            var currentIds = idsInput.val() ? idsInput.val().split(',') : [];
            
            attachment.forEach(function(media) {
                if (!currentIds.includes(media.id.toString())) {
                    currentIds.push(media.id);
                    var url = media.sizes && media.sizes.thumbnail ? media.sizes.thumbnail.url : media.url;
                    $('#ch-gallery-container').append(
                        '<div class="ch-gallery-item" data-id="'+media.id+'">' +
                        '<img src="'+url+'">' +
                        '<button type="button" class="remove-image">&times;</button></div>'
                    );
                }
            });
            idsInput.val(currentIds.join(','));
        });
        frame.open();
    });

    $('#ch-gallery-container').on('click', '.remove-image', function() {
        var item = $(this).closest('.ch-gallery-item');
        var idToRemove = item.data('id').toString();
        var idsInput = $('#ch_business_gallery');
        var newIds = idsInput.val().split(',').filter(function(id) { return id !== idToRemove; });
        idsInput.val(newIds.join(','));
        item.remove();
    });

    // --- LÓGICA DEL CONSTRUCTOR DE MENÚ ---
    let menuData = JSON.parse($('#ch_business_menu').val() || '[]');

    function renderMenu() {
        const container = $('#ch-categories-container');
        container.empty();

        menuData.forEach((category, catIndex) => {
            let itemsHtml = '';
            category.items.forEach((item, itemIndex) => {
                itemsHtml += `
                    <div class="ch-item-card">
                        <span class="ch-remove-btn" style="position:absolute; top: 10px; right: 15px;" onclick="chRemoveItem(${catIndex}, ${itemIndex})">Eliminar Plato</span>
                        <div class="ch-item-grid">
                            <input type="text" placeholder="Nombre (Ej: Empanada)" value="${escapeHtml(item.name)}" onchange="chUpdateItem(${catIndex}, ${itemIndex}, 'name', this.value)">
                            <input type="text" placeholder="Descripción" value="${escapeHtml(item.description)}" onchange="chUpdateItem(${catIndex}, ${itemIndex}, 'description', this.value)">
                            <input type="number" placeholder="Precio" value="${item.price}" onchange="chUpdateItem(${catIndex}, ${itemIndex}, 'price', this.value)">
                            <label>
                                <input type="checkbox" ${item.is_halal ? 'checked' : ''} onchange="chUpdateItem(${catIndex}, ${itemIndex}, 'is_halal', this.checked)"> ✅ Halal
                            </label>
                        </div>
                    </div>
                `;
            });

            container.append(`
                <div class="ch-category-card">
                    <div class="ch-category-header">
                        <input type="text" value="${escapeHtml(category.category)}" placeholder="Nombre Categoría (Ej: Platos Principales)" onchange="chUpdateCategory(${catIndex}, this.value)">
                        <span class="ch-remove-btn" onclick="chRemoveCategory(${catIndex})">Eliminar Categoría</span>
                    </div>
                    <div class="ch-items-wrapper">
                        ${itemsHtml}
                    </div>
                    <button type="button" class="button button-secondary button-small" onclick="chAddItem(${catIndex})">+ Añadir Plato</button>
                </div>
            `);
        });

        $('#ch_business_menu').val(JSON.stringify(menuData));
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    window.chUpdateCategory = function(index, value) { menuData[index].category = value; renderMenu(); };
    window.chRemoveCategory = function(index) { if(confirm('¿Eliminar esta categoría completa?')) { menuData.splice(index, 1); renderMenu(); } };
    window.chAddItem = function(catIndex) { menuData[catIndex].items.push({ name: '', description: '', price: '', is_halal: false }); renderMenu(); };
    window.chRemoveItem = function(catIndex, itemIndex) { menuData[catIndex].items.splice(itemIndex, 1); renderMenu(); };
    window.chUpdateItem = function(catIndex, itemIndex, field, value) { 
        menuData[catIndex].items[itemIndex][field] = (field === 'price') ? parseFloat(value) || 0 : value; 
        $('#ch_business_menu').val(JSON.stringify(menuData)); 
    };

    $('#ch_add_category_btn').on('click', function() {
        menuData.push({ category: 'Nueva Categoría', items: [] });
        renderMenu();
    });

    renderMenu();
});
</script>