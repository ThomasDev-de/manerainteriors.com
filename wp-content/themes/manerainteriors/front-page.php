<?php get_header(); ?>
    <main>

        <div id="homeCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
            <!-- Indikatoren (Punkte) -->
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#homeCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#homeCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
           </div>

            <!-- Slides -->
            <div class="carousel-inner">
                <div class="carousel-item active  carousel-split">
                    <div class="container-fluid px-0">
                        <div class="row g-0">
                            <div class="col-12 col-md-6 col-image">
                                <img
                                        src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/start/slide2.webp' ); ?>"
                                        class="img-fluid w-100"
                                        alt="Slide 1"
                                >
                            </div>
                            <div class="col-12 col-md-6 col-content d-flex align-items-center justify-content-center text-start">
                                <div class="px-4 px-md-5">
                                    <h2 class="mb-3">El lujo de la atemporalidad</h2>
                                    <p class="lead mb-0">
                                        En Manera Interiors, diseñamos muebles exclusivos que se convierten en el alma del hogar.
                                        Cada pieza combina lujo, funcionalidad y diseño atemporal para enriquecer cualquier ambiente.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="carousel-item">
                    <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/start/slider1.jpg' ); ?>" class="d-block w-100" alt="Slide 2">

                </div>
            </div>

            <!-- Pfeile -->
            <button class="carousel-control-prev" type="button" data-bs-target="#homeCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Zurück</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#homeCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Weiter</span>
            </button>
        </div>

        <section class="container my-5 py-5">
            <div class="row">
                <!-- schmaler und mittig: responsiv engere Breite + eigene Klasse für Max-Breite -->
                <div class="col-12 col-md-10 col-lg-7 col-xl-6 mx-auto text-start hero-intro">
                    <h2 class="mb-4">El lujo de la atemporalidad</h2>
                    <p class="mb-0">
                        En Manera Interiors, diseñamos muebles exclusivos que se convierten en el alma del hogar.
                        Cada pieza, creada con materiales nobles y acabados impecables, refleja un equilibrio perfecto
                        entre lujo, funcionalidad y diseño atemporal. Nuestra pasión por la artesanía nos permite
                        ofrecer mobiliario que trasciende modas y enriquece cualquier ambiente con personalidad.
                    </p>
                </div>
            </div>
        </section>

        <section class="container my-5 py-5">
            <div class="row">
                <div class="col-12 col-md-10 col-lg-7 col-xl-6 mx-auto text-center hero-intro">
                    <h4 class="mb-4">Categorías destacadas</h4>
                </div>
            </div>

            <div class="row g-3 g-md-4">
                <?php
                // Nur Top-Level-Kategorien (keine Subkategorien) laden
                $terms = get_terms(array(
                        'taxonomy'   => 'furniture_category',
                        'hide_empty' => false,
                        'parent'     => 0,          // <- wichtig: nur oberste Ebene
                        'orderby'    => 'name',
                        'order'      => 'ASC',
                ));

                if (!is_wp_error($terms) && !empty($terms)) :
                    foreach ($terms as $term) :
                        $thumb_id = get_term_meta($term->term_id, 'thumbnail_id', true);
                        $img = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'moebel-thumb') : get_template_directory_uri() . '/assets/img/placeholder-600x600.png';
                        $link = get_term_link($term);
                        ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <a href="<?php echo esc_url($link); ?>" class="text-decoration-none d-block">
                                <div class="card border-0 bg-transparent">
                                    <div class="ratio ratio-1x1 rounded overflow-hidden">
                                        <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($term->name); ?>" class="w-100 h-100" style="object-fit: cover;">
                                    </div>
                                    <div class="card-body px-0 text-center mt-2">
                                        <h3 class="h5 mb-0"><?php echo esc_html($term->name); ?></h3>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php
                    endforeach;
                else:
                    ?>
                    <div class="col-12">
                        <p>No categories available yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <div class="container my-5">
            <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                <?php the_content(); ?>
            <?php endwhile; endif; ?>
        </div>
    </main>
<?php get_footer(); ?>
