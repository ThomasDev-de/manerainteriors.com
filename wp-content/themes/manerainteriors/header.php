<?php
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<header class="site-header">
    <div class="nav-wrap">
        <nav class="nav-left">
            <?php
            wp_nav_menu(array(
                    'theme_location' => 'primary_left',
                    'container'      => false,
                    'menu_class'     => 'menu menu-left',
                    'fallback_cb'    => false
            ));
            ?>
        </nav>

        <div class="brand">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="brand-link">
                <?php bloginfo('name'); ?>
                <?php
                // Wenn du ein Logo-Bild verwenden möchtest, ersetze Zeile oben durch:
                // echo '<img src="' . esc_url(get_template_directory_uri() . '/assets/img/logo.svg') . '" alt="' . esc_attr(get_bloginfo('name')) . '">';
                ?>
            </a>
        </div>

        <nav class="nav-right">
            <?php
            wp_nav_menu(array(
                    'theme_location' => 'primary_right',
                    'container'      => false,
                    'menu_class'     => 'menu menu-right',
                    'fallback_cb'    => false
            ));
            ?>
        </nav>
    </div>
</header>
