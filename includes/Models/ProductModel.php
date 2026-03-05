<?php
if (!defined('ABSPATH')) exit;

class ChileHalal_Product_Model {

    public function __construct() {
        add_action('init', [$this, 'registerPostType']);
        add_action('init', [$this, 'registerTaxonomies']);
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('save_post', [$this, 'saveMeta']);
        
        add_action('admin_enqueue_scripts', [$this, 'enqueueMediaUploader']);
        add_action('ch_product_category_add_form_fields', [$this, 'addCategoryImageField'], 10, 2);
        add_action('ch_product_category_edit_form_fields', [$this, 'editCategoryImageField'], 10, 2);
        add_action('created_ch_product_category', [$this, 'saveCategoryImage'], 10, 2);
        add_action('edited_ch_product_category', [$this, 'saveCategoryImage'], 10, 2);
    }

    public function registerPostType() {
        register_post_type('ch_product', [
            'labels' => [
                'name' => 'Productos', 
                'singular_name' => 'Producto', 
                'add_new' => 'Nuevo Producto'
            ],
            'public' => true,
            'show_in_menu' => 'chilehalal-app',
            'supports' => ['title', 'thumbnail'],
            'taxonomies' => ['ch_product_category']
        ]);
    }

    public function registerTaxonomies() {
        register_taxonomy('ch_product_category', 'ch_product', [
            'labels' => [
                'name' => 'Categorías',
                'singular_name' => 'Categoría',
                'add_new_item' => 'Añadir Nueva Categoría'
            ],
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'categoria-producto'],
        ]);
    }

    public function addMetaBoxes() {
        add_meta_box(
            'ch_product_details', 
            'Ficha Técnica del Producto', 
            [$this, 'renderForm'], 
            'ch_product', 
            'normal', 
            'high'
        );
    }

    public function renderForm($post) {
        $barcode = get_post_meta($post->ID, '_ch_barcode', true);
        $is_halal = get_post_meta($post->ID, '_ch_is_halal', true);
        $brand = get_post_meta($post->ID, '_ch_brand', true);
        $description = get_post_meta($post->ID, '_ch_description', true);

        require CH_API_PATH . 'templates/metaboxes/product-meta.php';
    }

    public function saveMeta($post_id) {
        if (!isset($_POST['ch_product_nonce']) || !wp_verify_nonce($_POST['ch_product_nonce'], 'save_ch_product')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        $fields = ['ch_barcode', 'ch_is_halal', 'ch_brand', 'ch_description'];
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_textarea_field($_POST[$field]));
            }
        }
        
        if (isset($_POST['ch_brand'])) {
            delete_transient('ch_all_brands');
        }
    }

    public function enqueueMediaUploader($hook) {
        if ($hook === 'edit-tags.php' || $hook === 'term.php') {
            wp_enqueue_media();
        }
    }

    public function addCategoryImageField() {
        ?>
        <div class="form-field term-group">
            <label for="ch_category_image">Imagen (Thumbnail)</label>
            <input type="hidden" id="ch_category_image" name="ch_category_image" class="custom_media_url" value="">
            <div id="category-image-wrapper" style="margin: 10px 0;"></div>
            <p>
                <input type="button" class="button button-secondary ch_tax_media_button" value="Subir / Elegir Imagen" />
                <input type="button" class="button button-secondary ch_tax_media_remove" value="Eliminar Imagen" />
            </p>
        </div>
        <?php
        $this->printMediaScript();
    }

    public function editCategoryImageField($term) {
        $image_id = get_term_meta($term->term_id, '_ch_category_image', true);
        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
        ?>
        <tr class="form-field term-group-wrap">
            <th scope="row"><label for="ch_category_image">Imagen (Thumbnail)</label></th>
            <td>
                <input type="hidden" id="ch_category_image" name="ch_category_image" value="<?php echo esc_attr($image_id); ?>">
                <div id="category-image-wrapper" style="margin: 10px 0;">
                    <?php if ($image_url) : ?>
                        <img src="<?php echo esc_url($image_url); ?>" style="max-width: 150px; height: auto;" />
                    <?php endif; ?>
                </div>
                <p>
                    <input type="button" class="button button-secondary ch_tax_media_button" value="Subir / Cambiar Imagen" />
                    <input type="button" class="button button-secondary ch_tax_media_remove" value="Eliminar Imagen" />
                </p>
            </td>
        </tr>
        <?php
        $this->printMediaScript();
    }

    public function saveCategoryImage($term_id) {
        if (isset($_POST['ch_category_image'])) {
            update_term_meta($term_id, '_ch_category_image', sanitize_text_field($_POST['ch_category_image']));
        }
    }

    private function printMediaScript() {
        ?>
        <script>
        jQuery(document).ready(function($){
            var mediaUploader;
            $('.ch_tax_media_button').click(function(e) {
                e.preventDefault();
                if (mediaUploader) { 
                    mediaUploader.open(); 
                    return; 
                }
                mediaUploader = wp.media.frames.file_frame = wp.media({
                    title: 'Elegir Imagen',
                    button: { text: 'Seleccionar' },
                    multiple: false
                });
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#ch_category_image').val(attachment.id);
                    $('#category-image-wrapper').html('<img src="'+attachment.url+'" style="max-width:150px; height:auto;" />');
                });
                mediaUploader.open();
            });
            $('.ch_tax_media_remove').click(function(e){
                e.preventDefault();
                $('#ch_category_image').val('');
                $('#category-image-wrapper').html('');
            });
        });
        </script>
        <?php
    }
}