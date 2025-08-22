<?php
// Styles und Skripte laden
function manera_enqueue() {
    // Bootstrap CSS (CDN)
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', array(), '5.3.3');
    wp_enqueue_style('inter-font', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap', array(), null);
    wp_enqueue_style('roboto-font', 'https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap', array(), null);
    wp_enqueue_style('manera-style', get_stylesheet_uri(), array('bootstrap', 'inter-font', 'roboto-font'), '1.0.0');

    wp_enqueue_script('jquery');
    wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', array(), '5.3.3', true);
}
add_action('wp_enqueue_scripts', 'manera_enqueue');

// Theme-Supports und Menüs
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_image_size('moebel-thumb', 600, 600, true);
    add_image_size('carousel', 1920, 800, true);
    register_nav_menus(array(
        'primary_left'  => 'Hauptmenü links',
        'primary_right' => 'Hauptmenü rechts',
        'primary'       => 'Hauptmenü (alt)',
    ));
});

// Block-Editor (Gutenberg) abschalten – wir nutzen Classic/UI-gesteuerte Templates
add_filter('use_block_editor_for_post', '__return_false', 10);
add_filter('use_block_editor_for_post_type', '__return_false', 10);
add_filter('gutenberg_use_widgets_block_editor', '__return_false');
add_filter('use_widgets_block_editor', '__return_false');

// ... existing code ...

/**
 * Custom Post Type: Furniture + Taxonomy: Furniture Categories (with term image)
 */
add_action('init', function () {
    // CPT: furniture
    $labels = array(
        'name'               => 'Furniture',
        'singular_name'      => 'Furniture Item',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Furniture Item',
        'edit_item'          => 'Edit Furniture Item',
        'new_item'           => 'New Furniture Item',
        'view_item'          => 'View Furniture Item',
        'search_items'       => 'Search Furniture',
        'not_found'          => 'No furniture found',
        'not_found_in_trash' => 'No furniture found in Trash',
        'menu_name'          => 'Furniture',
    );
    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'rewrite'            => array('slug' => 'furniture', 'with_front' => false),
        'menu_icon'          => 'dashicons-screenoptions',
        'supports'           => array('title', 'editor', 'thumbnail', 'excerpt'),
        'show_in_rest'       => false,
    );
    register_post_type('furniture', $args);

    // Taxonomy: furniture_category (e.g., Tables, Sofas, Chairs)
    $tx_labels = array(
        'name'              => 'Furniture Categories',
        'singular_name'     => 'Furniture Category',
        'search_items'      => 'Search Categories',
        'all_items'         => 'All Categories',
        'parent_item'       => 'Parent Category',
        'parent_item_colon' => 'Parent Category:',
        'edit_item'         => 'Edit Category',
        'update_item'       => 'Update Category',
        'add_new_item'      => 'Add New Category',
        'new_item_name'     => 'New Category Name',
        'menu_name'         => 'Furniture Categories',
    );
    $tx_args = array(
        'hierarchical'      => true,
        'labels'            => $tx_labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array(
            'slug' => 'categoria-producto',
            'with_front' => false,
            'hierarchical' => true
        ),
        'public'            => true,
        'show_in_rest'      => false,
    );
    register_taxonomy('furniture_category', array('furniture'), $tx_args);
});

// Taxonomie-Archiv: /categoria-producto/sofas/cozy[/...]
add_action('init', function () {
    add_rewrite_rule(
        '^categoria-producto/(.+)/?$',
        'index.php?furniture_category=$matches[1]',
        'top'
    );
});

// Produkt-Single: /categoria-producto/.../produkt-slug (nicht Kategorie überschreiben)
add_action('init', function () {
    add_rewrite_rule(
        '^categoria-producto/(.+)/([^/]+)/?$',
        'index.php?post_type=furniture&name=$matches[2]',
        'top'
    );
});

// %furniture_category% im Produkt-Link durch kompletten Pfad ersetzen
add_filter('post_type_link', function ($permalink, $post, $leavename, $sample) {
    if ($post->post_type !== 'furniture') return $permalink;
    $terms = get_the_terms($post->ID, 'furniture_category');
    if (is_wp_error($terms) || empty($terms)) {
        return str_replace('%furniture_category%', 'uncategorized', $permalink);
    }
    $best = null; $depth = -1;
    foreach ($terms as $t) {
        $d = count(get_ancestors($t->term_id, 'furniture_category'));
        if ($d > $depth) { $depth = $d; $best = $t; }
    }
    $segments = array($best->slug);
    $parents = array_reverse(get_ancestors($best->term_id, 'furniture_category'));
    foreach ($parents as $pid) {
        $p = get_term($pid, 'furniture_category');
        if ($p && !is_wp_error($p)) array_unshift($segments, $p->slug);
    }
    $term_path = implode('/', $segments);
    return str_replace('%furniture_category%', $term_path, $permalink);
}, 10, 4);

// Beim Themewechsel Rewrites flushen
add_action('after_switch_theme', function () { flush_rewrite_rules(); });

// ... existing code ...

/**
 * Furniture Details Metabox (width, height, depth, price, color, material, sku)
 */
function mi_furniture_add_metabox() {
    add_meta_box(
        'mi_furniture_details',
        'Furniture Details',
        'mi_furniture_metabox_render',
        'furniture',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'mi_furniture_add_metabox');

function mi_furniture_metabox_render($post) {
    wp_nonce_field('mi_furniture_save', 'mi_furniture_nonce');
    $width    = get_post_meta($post->ID, '_width', true);
    $height   = get_post_meta($post->ID, '_height', true);
    $depth    = get_post_meta($post->ID, '_depth', true);
    $price    = get_post_meta($post->ID, '_price', true);
    $color    = get_post_meta($post->ID, '_color', true);
    $material = get_post_meta($post->ID, '_material', true);
    $sku      = get_post_meta($post->ID, '_sku', true);

    // Downloads (Attachment-IDs, komma-getrennt)
    $downloads_raw = get_post_meta($post->ID, '_downloads', true);
    $download_ids  = array_filter(array_map('intval', explode(',', (string)$downloads_raw)));

    // GALLERY (Bild-IDs, komma-getrennt)
    $gallery_raw = get_post_meta($post->ID, '_gallery', true);
    $gallery_ids = array_filter(array_map('intval', explode(',', (string)$gallery_raw)));
    ?>
    <table class="form-table">
        <tr>
            <th><label for="mi_width">Width (cm)</label></th>
            <td><input type="text" id="mi_width" name="mi_width" value="<?php echo esc_attr($width); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="mi_height">Height (cm)</label></th>
            <td><input type="text" id="mi_height" name="mi_height" value="<?php echo esc_attr($height); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="mi_depth">Depth (cm)</label></th>
            <td><input type="text" id="mi_depth" name="mi_depth" value="<?php echo esc_attr($depth); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="mi_price">Price</label></th>
            <td><input type="text" id="mi_price" name="mi_price" value="<?php echo esc_attr($price); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="mi_color">Color</label></th>
            <td><input type="text" id="mi_color" name="mi_color" value="<?php echo esc_attr($color); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="mi_material">Material</label></th>
            <td><input type="text" id="mi_material" name="mi_material" value="<?php echo esc_attr($material); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="mi_sku">SKU</label></th>
            <td><input type="text" id="mi_sku" name="mi_sku" value="<?php echo esc_attr($sku); ?>" class="regular-text" /></td>
        </tr>
    </table>

    <hr>

    <h3 style="margin-top:1em;">Downloads</h3>
    <p class="description">Attach one or more files users can download (PDF, images, ZIP, ...).</p>

    <div id="mi-downloads-list" style="margin:8px 0;">
        <?php if (!empty($download_ids)) : ?>
            <ul style="margin:0;padding-left:18px;">
                <?php foreach ($download_ids as $aid) :
                    $url = wp_get_attachment_url($aid);
                    $title = get_the_title($aid);
                    if (!$url) continue; ?>
                    <li data-id="<?php echo esc_attr($aid); ?>">
                        <span><?php echo esc_html($title ?: basename($url)); ?></span>
                        <a href="#" class="mi-remove-download" style="margin-left:8px;">Remove</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <em>No files selected.</em>
        <?php endif; ?>
    </div>

    <input type="hidden" id="mi_downloads" name="mi_downloads" value="<?php echo esc_attr(implode(',', $download_ids)); ?>" />
    <p>
        <button type="button" class="button" id="mi-add-downloads">Add files</button>
        <button type="button" class="button" id="mi-clear-downloads" style="margin-left:6px;">Clear all</button>
    </p>

    <hr>

    <h3 style="margin-top:1em;">Gallery images</h3>
    <p class="description">Select one or more images to show as small thumbnails on the product page.</p>

    <div id="mi-gallery-list" style="margin:8px 0; display:flex; flex-wrap:wrap; gap:8px;">
        <?php if (!empty($gallery_ids)) : ?>
            <?php foreach ($gallery_ids as $gid) :
                $src = wp_get_attachment_image_url($gid, 'thumbnail');
                if (!$src) continue; ?>
                <div class="mi-gal-item" data-id="<?php echo esc_attr($gid); ?>" style="position:relative;">
                    <img src="<?php echo esc_url($src); ?>" style="width:80px;height:80px;object-fit:cover;border:1px solid #ddd;border-radius:4px;">
                    <a href="#" class="mi-remove-gallery" title="Remove" style="position:absolute;right:2px;top:2px;background:#fff;border:1px solid #ccc;border-radius:2px;padding:0 4px;line-height:18px;text-decoration:none;">×</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <em>No images selected.</em>
        <?php endif; ?>
    </div>

    <input type="hidden" id="mi_gallery" name="mi_gallery" value="<?php echo esc_attr(implode(',', $gallery_ids)); ?>" />
    <p>
        <button type="button" class="button" id="mi-add-gallery">Add images</button>
        <button type="button" class="button" id="mi-clear-gallery" style="margin-left:6px;">Clear all</button>
    </p>

    <script type="text/javascript">
        jQuery(function($){
            // Downloads (bereits vorhanden) ...

            // Gallery
            var gFrame, $gHidden = $('#mi_gallery'), $gList = $('#mi-gallery-list');

            function gRender(ids) {
                if (!ids.length) { $gList.html('<em>No images selected.</em>'); return; }
                var html = '';
                ids.forEach(function(id){
                    html += '<div class="mi-gal-item" data-id="'+id+'" style="position:relative;">' +
                        '<img src="'+ (wp.media.attachment(id).get("sizes") && wp.media.attachment(id).get("sizes").thumbnail ? wp.media.attachment(id).get("sizes").thumbnail.url : wp.media.attachment(id).get("url")) +'" style="width:80px;height:80px;object-fit:cover;border:1px solid #ddd;border-radius:4px;">' +
                        '<a href="#" class="mi-remove-gallery" title="Remove" style="position:absolute;right:2px;top:2px;background:#fff;border:1px solid #ccc;border-radius:2px;padding:0 4px;line-height:18px;text-decoration:none;">×</a>' +
                        '</div>';
                });
                $gList.html(html);
            }

            $('#mi-add-gallery').on('click', function(e){
                e.preventDefault();
                gFrame = wp.media({
                    title: 'Select images',
                    button: { text: 'Use images' },
                    multiple: true,
                    library: { type: 'image' }
                });
                gFrame.on('select', function(){
                    var selection = gFrame.state().get('selection');
                    var current = $gHidden.val() ? $gHidden.val().split(',').filter(Boolean) : [];
                    selection.each(function(att){
                        var id = String(att.get('id'));
                        if (current.indexOf(id) === -1) current.push(id);
                    });
                    $gHidden.val(current.join(','));
                    gRender(current);
                });
                gFrame.open();
            });

            $gList.on('click', '.mi-remove-gallery', function(e){
                e.preventDefault();
                var $it = $(this).closest('.mi-gal-item'), id = String($it.data('id'));
                var arr = $gHidden.val() ? $gHidden.val().split(',').filter(Boolean) : [];
                arr = arr.filter(function(x){ return x !== id; });
                $gHidden.val(arr.join(','));
                if (arr.length) $it.remove(); else $gList.html('<em>No images selected.</em>');
            });

            $('#mi-clear-gallery').on('click', function(e){
                e.preventDefault();
                $gHidden.val('');
                $gList.html('<em>No images selected.</em>');
            });
        });
    </script>
    <?php
}

function mi_fc_add_form_fields() { ?>
    <div class="form-field">
        <label for="furniture_category_preview_id">Preview image</label>
        <div id="fc_preview_preview" style="margin-bottom:8px;"></div>
        <input type="hidden" name="furniture_category_preview_id" id="furniture_category_preview_id" value="">
        <button type="button" class="button" id="fc_preview_btn">Select image</button>
    </div>
    <div class="form-field">
        <label for="furniture_category_header_id">Header image</label>
        <div id="fc_header_preview" style="margin-bottom:8px;"></div>
        <input type="hidden" name="furniture_category_header_id" id="furniture_category_header_id" value="">
        <button type="button" class="button" id="fc_header_btn">Select image</button>
    </div>
    <script type="text/javascript">
        jQuery(function($){
            var frame;
            function openPicker(targetId, previewId){
                if (frame) { frame.open(); return; }
                frame = wp.media({ title: 'Choose image', button: { text: 'Use image' }, multiple: false });
                frame.on('select', function(){
                    var at = frame.state().get('selection').first().toJSON();
                    $('#' + targetId).val(at.id);
                    $('#' + previewId).html('<img src="'+at.url+'" style="max-width:150px;height:auto;">');
                });
                frame.open();
            }
            $('#fc_preview_btn').on('click', function(e){ e.preventDefault(); openPicker('furniture_category_preview_id','fc_preview_preview'); });
            $('#fc_header_btn').on('click', function(e){ e.preventDefault(); openPicker('furniture_category_header_id','fc_header_preview'); });
        });
    </script>
<?php }
add_action('furniture_category_add_form_fields', 'mi_fc_add_form_fields', 10, 2);

function mi_fc_edit_form_fields($term) {
    $preview_id = get_term_meta($term->term_id, 'preview_id', true);
    $header_id  = get_term_meta($term->term_id, 'header_id', true);
    $preview_src = $preview_id ? wp_get_attachment_image_url($preview_id, 'medium') : '';
    $header_src  = $header_id  ? wp_get_attachment_image_url($header_id,  'large')  : '';
    ?>
    <tr class="form-field">
        <th scope="row"><label for="furniture_category_preview_id">Preview image</label></th>
        <td>
            <div id="fc_preview_preview" style="margin-bottom:8px;">
                <?php if ($preview_src): ?><img src="<?php echo esc_url($preview_src); ?>" style="max-width:150px;height:auto;"><?php endif; ?>
            </div>
            <input type="hidden" name="furniture_category_preview_id" id="furniture_category_preview_id" value="<?php echo esc_attr($preview_id); ?>">
            <button type="button" class="button" id="fc_preview_btn"><?php echo $preview_src ? 'Change image' : 'Select image'; ?></button>
            <button type="button" class="button" id="fc_preview_remove" <?php echo $preview_src ? '' : 'style="display:none"'; ?>>Remove</button>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="furniture_category_header_id">Header image</label></th>
        <td>
            <div id="fc_header_preview" style="margin-bottom:8px;">
                <?php if ($header_src): ?><img src="<?php echo esc_url($header_src); ?>" style="max-width:300px;height:auto;"><?php endif; ?>
            </div>
            <input type="hidden" name="furniture_category_header_id" id="furniture_category_header_id" value="<?php echo esc_attr($header_id); ?>">
            <button type="button" class="button" id="fc_header_btn"><?php echo $header_src ? 'Change image' : 'Select image'; ?></button>
            <button type="button" class="button" id="fc_header_remove" <?php echo $header_src ? '' : 'style="display:none"'; ?>>Remove</button>

            <script type="text/javascript">
                jQuery(function($){
                    function mediaPicker(targetId, previewId, removeBtnId) {
                        var frame = wp.media({ title: 'Choose image', button: { text: 'Use image' }, multiple: false });
                        frame.on('select', function(){
                            var at = frame.state().get('selection').first().toJSON();
                            $('#'+targetId).val(at.id);
                            $('#'+previewId).html('<img src="'+at.url+'" style="max-width:300px;height:auto;">');
                            $('#'+removeBtnId).show();
                        });
                        frame.open();
                    }
                    $('#fc_preview_btn').on('click', function(e){ e.preventDefault(); mediaPicker('furniture_category_preview_id','fc_preview_preview','fc_preview_remove'); });
                    $('#fc_preview_remove').on('click', function(e){ e.preventDefault(); $('#furniture_category_preview_id').val(''); $('#fc_preview_preview').html(''); $(this).hide(); });

                    $('#fc_header_btn').on('click', function(e){ e.preventDefault(); mediaPicker('furniture_category_header_id','fc_header_preview','fc_header_remove'); });
                    $('#fc_header_remove').on('click', function(e){ e.preventDefault(); $('#furniture_category_header_id').val(''); $('#fc_header_preview').html(''); $(this).hide(); });
                });
            </script>
        </td>
    </tr>
    <?php
}
add_action('furniture_category_edit_form_fields', 'mi_fc_edit_form_fields', 10, 2);
function mi_furniture_save_meta($post_id) {
    if (!isset($_POST['mi_furniture_nonce']) || !wp_verify_nonce($_POST['mi_furniture_nonce'], 'mi_furniture_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (get_post_type($post_id) !== 'furniture') return;

    // Alte Download-IDs vor dem Update sichern
    $old_csv = get_post_meta($post_id, '_downloads', true);
    $old_ids = array_filter(array_map('intval', explode(',', (string)$old_csv)));

    // Alte Gallery-IDs vor dem Update sichern
    $old_g_csv = get_post_meta($post_id, '_gallery', true);
    $old_g_ids = array_filter(array_map('intval', explode(',', (string)$old_g_csv)));

    $map = array(
        '_width'    => isset($_POST['mi_width']) ? sanitize_text_field($_POST['mi_width']) : '',
        '_height'   => isset($_POST['mi_height']) ? sanitize_text_field($_POST['mi_height']) : '',
        '_depth'    => isset($_POST['mi_depth']) ? sanitize_text_field($_POST['mi_depth']) : '',
        '_price'    => isset($_POST['mi_price']) ? sanitize_text_field($_POST['mi_price']) : '',
        '_color'    => isset($_POST['mi_color']) ? sanitize_text_field($_POST['mi_color']) : '',
        '_material' => isset($_POST['mi_material']) ? sanitize_text_field($_POST['mi_material']) : '',
        '_sku'      => isset($_POST['mi_sku']) ? sanitize_text_field($_POST['mi_sku']) : '',
        '_downloads'=> isset($_POST['mi_downloads']) ? implode(',', array_filter(array_map('intval', explode(',', $_POST['mi_downloads'])))) : '',
        '_gallery'  => isset($_POST['mi_gallery']) ? implode(',', array_filter(array_map('intval', explode(',', $_POST['mi_gallery'])))) : '',
    );

    foreach ($map as $key => $val) {
        if ($val !== '') update_post_meta($post_id, $key, $val);
        else delete_post_meta($post_id, $key);
    }

    // Entfernte Downloads löschen (wie vorher)
    $new_ids = array_filter(array_map('intval', explode(',', (string)$map['_downloads'])));
    $removed = array_diff($old_ids, $new_ids);
    if (!empty($removed)) {
        foreach ($removed as $aid) {
            $att = get_post($aid);
            if ($att && $att->post_type === 'attachment' && current_user_can('delete_post', $aid)) {
                wp_delete_attachment($aid, true);
            }
        }
    }

    // Optional: entfernte Gallery-Bilder ebenfalls löschen (falls gewünscht)
    $new_g_ids = array_filter(array_map('intval', explode(',', (string)$map['_gallery'])));
    $g_removed = array_diff($old_g_ids, $new_g_ids);
    if (!empty($g_removed)) {
        foreach ($g_removed as $gid) {
            $att = get_post($gid);
            if ($att && $att->post_type === 'attachment' && current_user_can('delete_post', $gid)) {
                wp_delete_attachment($gid, true);
            }
        }
    }
}
add_action('save_post_furniture', 'mi_furniture_save_meta');

// Beim Löschen eines Furniture-Posts: alle verknüpften Downloads endgültig löschen
function mi_furniture_delete_downloads_on_trash($post_id) {
    if (get_post_type($post_id) !== 'furniture') return;

    $csv = get_post_meta($post_id, '_downloads', true);
    $ids = array_filter(array_map('intval', explode(',', (string)$csv)));
    if (empty($ids)) return;

    foreach ($ids as $aid) {
        $att = get_post($aid);
        if ($att && $att->post_type === 'attachment' && current_user_can('delete_post', $aid)) {
            wp_delete_attachment($aid, true);
        }
    }
}
add_action('before_delete_post', 'mi_furniture_delete_downloads_on_trash');

// Color Picker & Media im Admin laden
function mi_furniture_admin_assets($hook) {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'furniture') {
        wp_enqueue_media();
    }
}
function mi_fc_save_term_meta($term_id) {
    // Speichere beide Felder
    if (isset($_POST['furniture_category_preview_id'])) {
        $id = (int) $_POST['furniture_category_preview_id'];
        if ($id) update_term_meta($term_id, 'preview_id', $id); else delete_term_meta($term_id, 'preview_id');
    }
    if (isset($_POST['furniture_category_header_id'])) {
        $id = (int) $_POST['furniture_category_header_id'];
        if ($id) update_term_meta($term_id, 'header_id', $id); else delete_term_meta($term_id, 'header_id');
    }
}
add_action('created_furniture_category', 'mi_fc_save_term_meta', 10);
add_action('edited_furniture_category',  'mi_fc_save_term_meta', 10);

// Medien-Skripte im Taxonomie-Admin laden (bleibt)
add_action('admin_enqueue_scripts', function () {
    $screen = get_current_screen();
    if ($screen && isset($screen->taxonomy) && $screen->taxonomy === 'furniture_category') {
        wp_enqueue_media();
    }
});

add_action('admin_enqueue_scripts', 'mi_furniture_admin_assets');

// ... existing code ...
