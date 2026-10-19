<?php
/** Home page template. */
get_header();
?>
<main id="main">
    <section class="adim-hero" aria-labelledby="adim-hero-title">
        <div class="adim-hero-inner">
            <p class="adim-eyebrow"><?php esc_html_e('ADIM AYAKKABI • YENİ SEZON', 'adim'); ?></p>
            <h1 id="adim-hero-title"><?php esc_html_e('Tarzına Yeni Bir Adım.', 'adim'); ?></h1>
            <p><?php esc_html_e('Günlük yaşam ve spora uyum sağlayan modelleri keşfet.', 'adim'); ?></p>
            <a class="adim-button" href="<?php echo esc_url(adim_shop_url()); ?>"><?php esc_html_e('Modelleri İncele', 'adim'); ?> <span aria-hidden="true">→</span></a>
        </div>
    </section>
    <?php if (taxonomy_exists('product_cat')) : ?>
        <?php
        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'number' => 4,
            'orderby' => 'count',
            'order' => 'DESC',
        ]);
        ?>
        <?php if (!is_wp_error($categories) && $categories) : ?>
            <section class="adim-container" aria-labelledby="adim-categories-title">
                <div class="adim-section-heading">
                    <h2 id="adim-categories-title"><?php esc_html_e('Kategoriler', 'adim'); ?></h2>
                </div>
                <div class="adim-category-grid">
                    <?php foreach ($categories as $category) : ?>
                        <?php $url = get_term_link($category); ?>
                        <?php if (!is_wp_error($url)) : ?>
                            <a class="adim-category-card" href="<?php echo esc_url($url); ?>">
                                <span><?php echo esc_html($category->name); ?></span><span aria-hidden="true">↗</span>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>
    <section class="adim-container adim-products" aria-labelledby="adim-new-arrivals">
        <div class="adim-section-heading">
            <h2 id="adim-new-arrivals"><?php esc_html_e('Yeni Gelenler', 'adim'); ?></h2>
            <a href="<?php echo esc_url(adim_shop_url()); ?>"><?php esc_html_e('Tümünü Gör', 'adim'); ?> →</a>
        </div>
        <?php if (class_exists('WooCommerce')) : ?>
            <?php echo do_shortcode('[products limit="8" columns="4" orderby="date" order="DESC"]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <?php else : ?>
            <p><?php esc_html_e('Koleksiyon yakında yayında.', 'adim'); ?></p>
        <?php endif; ?>
    </section>
    <section class="adim-store-banner" aria-labelledby="adim-store-title">
        <div class="adim-container">
            <p class="adim-eyebrow"><?php esc_html_e('FİZİKSEL MAĞAZAMIZ', 'adim'); ?></p>
            <h2 id="adim-store-title"><?php esc_html_e('Mağazada deneyin, internetten keşfedin.', 'adim'); ?></h2>
            <p><?php esc_html_e('Teslimat, iade ve iletişim bilgilerini mağaza açılışında burada bulabilirsiniz.', 'adim'); ?></p>
        </div>
    </section>
</main>
<?php get_footer(); ?>
