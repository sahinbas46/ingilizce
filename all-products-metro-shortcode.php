<?php
add_shortcode('stockline_all_products_metro', function() {
    global $wpdb;
    $table = $wpdb->prefix.'stockline_products';
    $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC", ARRAY_A);
    ob_start();
    ?>
    <div class="unsplash-masonry-gallery">
        <?php foreach ($rows as $row):
            $w = intval($row['img_width']);
            $h = intval($row['img_height']);
            $ratio = ($w && $h) ? ($w / $h) : 1;
            $extra = '';
            if ($ratio > 1.7)      $extra = 'masonry-wide';
            elseif ($ratio < 0.65) $extra = 'masonry-tall';
            $slug = isset($row['slug']) && $row['slug'] ? $row['slug'] : sanitize_title($row['title']);
        ?>
        <div class="masonry-item <?= $extra ?>">
            <a href="<?= esc_url(site_url('/images/product/'.$slug.'-'.$row['id'])) ?>">
                <img src="<?= esc_url($row['img_url']) ?>"
                     alt="<?= esc_attr($row['title']) ?>"
                     loading="lazy"
                     width="<?= $w ?: 800 ?>"
                     height="<?= $h ?: 600 ?>">
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <style>
    /* ... masonry css ... */
    </style>
    <?php
    return ob_get_clean();
});

add_shortcode('stockline_collection_metro', function($atts){
    global $wpdb;
    $atts = shortcode_atts(['id'=>0], $atts);
    $collection_id = intval($atts['id']);
    if ($collection_id <= 0) return '';
    $items_table = $wpdb->prefix.'stockline_collection_items';
    $product_table = $wpdb->prefix.'stockline_products';
    $ids = $wpdb->get_col($wpdb->prepare("SELECT product_id FROM $items_table WHERE collection_id=%d", $collection_id));
    if (!$ids) return '<div>No products in this collection.</div>';
    $rows = $wpdb->get_results("SELECT * FROM $product_table WHERE id IN (".implode(',',$ids).")", ARRAY_A);
    ob_start();
    ?>
    <div class="unsplash-masonry-gallery">
        <?php foreach ($rows as $row):
            $w = intval($row['img_width']);
            $h = intval($row['img_height']);
            $ratio = ($w && $h) ? ($w / $h) : 1;
            $extra = '';
            if ($ratio > 1.7)      $extra = 'masonry-wide';
            elseif ($ratio < 0.65) $extra = 'masonry-tall';
            $slug = isset($row['slug']) && $row['slug'] ? $row['slug'] : sanitize_title($row['title']);
        ?>
        <div class="masonry-item <?= $extra ?>">
            <a href="<?= esc_url(site_url('/images/product/'.$slug.'-'.$row['id'])) ?>">
                <img src="<?= esc_url($row['img_url']) ?>"
                     alt="<?= esc_attr($row['title']) ?>"
                     loading="lazy"
                     width="<?= $w ?: 800 ?>"
                     height="<?= $h ?: 600 ?>">
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <style>
    /* ... masonry css ... */
    </style>
    <?php
    return ob_get_clean();
});