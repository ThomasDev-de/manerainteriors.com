<?php get_header(); ?>
<?php
//exit('single-furniture.php');
?>
    <main class="container my-5">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>

            <article <?php post_class('row g-4'); ?>>
                <div class="col-12 col-lg-6">
                    <?php if (has_post_thumbnail()) : the_post_thumbnail('large', array('class' => 'w-100 h-auto rounded')); endif; ?>

                    <?php
                    // Kleine Galerie-Thumbnails anzeigen
                    $gallery_raw = get_post_meta(get_the_ID(), '_gallery', true);
                    $gallery_ids = array_filter(array_map('intval', explode(',', (string)$gallery_raw)));
                    if (!empty($gallery_ids)) :
                        ?>
                        <div class="row g-2 mt-3">
                            <?php foreach ($gallery_ids as $gid) :
                                $thumb = wp_get_attachment_image_src($gid, 'thumbnail');
                                $full  = wp_get_attachment_image_url($gid, 'large');
                                if (!$thumb) continue; ?>
                                <div class="col-3 col-sm-2">
                                    <a href="<?php echo esc_url($full); ?>" target="_blank" rel="noopener">
                                        <img src="<?php echo esc_url($thumb[0]); ?>" class="img-fluid rounded" alt="">
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-12 col-lg-6">
                    <h1 class="mb-3"><?php the_title(); ?></h1>
                    <div class="mb-3 text-muted">
                        <?php
                        $terms = get_the_terms(get_the_ID(), 'furniture_category');
                        if ($terms && !is_wp_error($terms)) {
                            $links = array();
                            foreach ($terms as $t) { $links[] = '<a href="' . esc_url(get_term_link($t)) . '">' . esc_html($t->name) . '</a>'; }
                            echo implode(' · ', $links);
                        }
                        ?>
                    </div>

                    <?php
                    $width    = get_post_meta(get_the_ID(), '_width', true);
                    $height   = get_post_meta(get_the_ID(), '_height', true);
                    $depth    = get_post_meta(get_the_ID(), '_depth', true);
                    $price    = get_post_meta(get_the_ID(), '_price', true);
                    $color    = get_post_meta(get_the_ID(), '_color', true);
                    $material = get_post_meta(get_the_ID(), '_material', true);
                    $sku      = get_post_meta(get_the_ID(), '_sku', true);
                    ?>
                    <?php if ($width || $height || $depth || $price || $color || $material || $sku) : ?>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm">
                                <tbody>
                                <?php if ($sku): ?>
                                    <tr><th class="w-25">SKU</th><td><?php echo esc_html($sku); ?></td></tr>
                                <?php endif; ?>
                                <?php if ($width || $height || $depth): ?>
                                    <tr><th>Dimensions</th><td><?php echo esc_html(trim(sprintf('%s x %s x %s cm', $width, $height, $depth))); ?></td></tr>
                                <?php endif; ?>
                                <?php if ($color): ?>
                                    <tr><th>Color</th><td><?php echo esc_html($color); ?></td></tr>
                                <?php endif; ?>
                                <?php if ($material): ?>
                                    <tr><th>Material</th><td><?php echo esc_html($material); ?></td></tr>
                                <?php endif; ?>
                                <?php if ($price): ?>
                                    <tr><th>Price</th><td><?php echo esc_html($price); ?></td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <?php
                    // Downloads
                    $downloads_raw = get_post_meta(get_the_ID(), '_downloads', true);
                    $download_ids  = array_filter(array_map('intval', explode(',', (string)$downloads_raw)));
                    if (!empty($download_ids)) :
                        ?>
                        <div class="mb-4">
                            <h2 class="h5">Downloads</h2>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($download_ids as $aid) :
                                    $url   = wp_get_attachment_url($aid);
                                    if (!$url) continue;
                                    $title = get_the_title($aid);
                                    $file  = get_attached_file($aid);
                                    $size  = ($file && file_exists($file)) ? filesize($file) : 0;
                                    if ($size >= 1048576)      { $size_str = number_format_i18n($size / 1048576, 1) . ' MB'; }
                                    elseif ($size >= 1024)     { $size_str = number_format_i18n($size / 1024, 0) . ' KB'; }
                                    else                        { $size_str = $size . ' B'; }
                                    ?>
                                    <li class="mb-1">
                                        <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener">
                                            <?php echo esc_html($title ?: basename($url)); ?>
                                        </a>
                                        <small class="text-muted">(<?php echo esc_html($size_str); ?>)</small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="lead mb-4"><?php the_excerpt(); ?></div>
                    <div class="content mb-4"><?php the_content(); ?></div>
                </div>
            </article>

        <?php endwhile; endif; ?>
    </main>
<?php get_footer(); ?>
