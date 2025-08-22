<?php get_header(); ?>
    <main class="site-main">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <article <?php post_class(); ?>>
                <h1><?php the_title(); ?></h1>
                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
            </article>
        <?php endwhile; else: ?>
            <p>Keine Inhalte gefunden.</p>
        <?php endif; ?>
    </main>
<?php get_footer(); ?>
