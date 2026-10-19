<!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?>
<header class="adim-header"><div class="adim-header-inner"><a class="adim-brand" href="<?php echo esc_url(home_url('/')); ?>">ADIM <span>AYAKKABI</span></a><nav class="adim-nav" aria-label="<?php esc_attr_e('Primary navigation', 'adim'); ?>"><?php if (has_nav_menu('primary')) { wp_nav_menu(['theme_location'=>'primary','container'=>false]); } else { echo '<a href="' . esc_url(home_url('/shop/')) . '">' . esc_html__('Shop', 'adim') . '</a>'; } ?></nav></div></header>
