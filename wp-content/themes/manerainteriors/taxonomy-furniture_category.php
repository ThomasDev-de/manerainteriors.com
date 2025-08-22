<?php
get_header(); ?>
    <main>
        <?php
        $term = get_queried_object();
        // Header image (Hero)
        $term_header_id = get_term_meta($term->term_id, 'header_id', true);
        $term_img = $term_header_id ? wp_get_attachment_image_url($term_header_id, 'full') : '';
        ?>

        <div class="container-fluid p-0">
            <div class="position-relative d-flex align-items-center justify-content-center text-center"
                 style="background-image: url('<?php echo esc_url($term_img); ?>'); background-repeat: no-repeat; background-size: cover; background-position: center; min-height: 50vh;">
                <div class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-50"></div>
                <div class="position-relative py-5 px-3">
                    <h1 class="display-5 fw-light mb-2"><?php echo esc_html($term->name); ?></h1>
                    <?php if (!empty($term->description)) : ?>
                        <p class="lead mb-0"><?php echo esc_html($term->description); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="container my-5">
            <?php
            $children = get_terms(array(
                'taxonomy'   => 'furniture_category',
                'hide_empty' => false,
                'parent'     => $term->term_id,
            ));
            ?>

            <?php if (!is_wp_error($children) && !empty($children)) : ?>
                <div class="row g-3 g-md-4">
                    <?php foreach ($children as $child) :
                        // Preview image für Kachel
                        $preview_id = get_term_meta($child->term_id, 'preview_id', true);
                        $img = $preview_id ? wp_get_attachment_image_url($preview_id, 'moebel-thumb') : get_template_directory_uri() . '/assets/img/placeholder-600x600.png';
                        $link = get_term_link($child);
                        ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <a href="<?php echo esc_url($link); ?>" class="text-decoration-none d-block">
                                <div class="card border-0 bg-transparent">
                                    <div class="ratio ratio-1x1 rounded overflow-hidden">
                                        <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($child->name); ?>" class="w-100 h-100" style="object-fit: contain;">
                                    </div>
                                    <div class="card-body px-0 text-center mt-2">
                                        <h2 class="h5 mb-0"><?php echo esc_html($child->name); ?></h2>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else : ?>
                <?php
                // Produkte im gewählten Typ
                $paged = max(1, get_query_var('paged'));
                $q = new WP_Query(array(
                    'post_type'      => 'furniture',
                    'posts_per_page' => 12,
                    'paged'          => $paged,
                    'tax_query'      => array(
                        array(
                            'taxonomy' => 'furniture_category',
                            'field'    => 'term_id',
                            'terms'    => $term->term_id,
                        )
                    ),
                ));
                ?>
                <?php if ($q->have_posts()) : ?>
                    <div class="row g-3 g-md-4">
                        <?php while ($q->have_posts()) : $q->the_post(); ?>
                            <div class="col-6 col-md-4 col-lg-3">
                                <a href="<?php the_permalink(); ?>" class="text-decoration-none d-block">
                                    <div class="card border-0 bg-transparent">
                                        <div class="ratio ratio-1x1 rounded overflow-hidden">
                                            <?php if (has_post_thumbnail()) : ?>
                                                <?php the_post_thumbnail('moebel-thumb', array('class' => 'w-100 h-100', 'style' => 'object-fit:cover;')); ?>
                                            <?php else : ?>
                                                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/placeholder-600x600.png'); ?>" class="w-100 h-100" style="object-fit:cover;" alt="<?php the_title_attribute(); ?>">
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-body px-0 text-center mt-2">
                                            <h3 class="h6 mb-1"><?php the_title(); ?></h3>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>

                    <div class="mt-4">
                        <?php echo paginate_links(array('total' => $q->max_num_pages)); ?>
                    </div>
                <?php else : ?>
                    <p class="mb-0">No items found in this category.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
<?php get_footer(); ?>
