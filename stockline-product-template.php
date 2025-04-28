<?php
if (!defined('ABSPATH')) exit;
global $wpdb;
$product_id = intval(get_query_var('stockline_product_id'));
$table = $wpdb->prefix . 'stockline_products';
$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $product_id), ARRAY_A);

if (!$row) { 
    get_header(); 
    echo '<main style="max-width:700px;margin:100px auto 200px auto;text-align:center;font-size:1.3em;color:#888;">Product not found.</main>'; 
    get_footer(); 
    exit; 
}
$tags = array_filter(array_map('trim', preg_split('/[\s,;]+/', $row['tags'])));
function stockline_product_url($row) {
    $slug = isset($row['slug']) && $row['slug'] ? $row['slug'] : sanitize_title($row['title']);
    return site_url('/images/product/'.$slug.'-'.$row['id']);
}
?>
<?php get_header(); ?>
<style>
/* ... (same style as önceki örneklerde) ... */
</style>
<main class="stockline-single-root">
    <article class="stockline-single-card">
        <div class="stockline-single-media">
            <div class="stockline-single-media-inner">
                <img src="<?= esc_url($row['img_url']) ?>" alt="<?= esc_attr($row['title']) ?>"
                     width="<?= intval($row['img_width']) ?>" height="<?= intval($row['img_height']) ?>">
            </div>
        </div>
        <div class="stockline-single-content">
            <h1 class="stockline-single-title"><?= esc_html($row['title']); ?></h1>
            <?php if (!empty($row['desc_text'])): ?>
                <h2 class="stockline-single-desc"><?= esc_html($row['desc_text']) ?></h2>
            <?php endif; ?>
            <?php if ($row['img_width'] && $row['img_height']): ?>
                <div class="stockline-single-resolution">
                    Resolution: <?= intval($row['img_width']) . 'x' . intval($row['img_height']) ?>px
                </div>
            <?php endif; ?>
            <div class="stockline-single-info">
                <?php if ($row['img_size']): ?>
                    <span>Size: <b><?= round($row['img_size']/1048576,2) ?> MB</b></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($row['dropbox_path'])): ?>
                <a href="#" id="stockline-download-btn" class="stockline-single-download" data-id="<?= $row['id'] ?>">
                    Download High Resolution
                </a>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var btn = document.getElementById('stockline-download-btn');
                    if(btn) {
                        btn.addEventListener('click', function(e) {
                            e.preventDefault();
                            btn.textContent = "Preparing Download...";
                            fetch("<?= admin_url('admin-ajax.php') ?>?action=stockline_get_dropbox_link&id=" + btn.getAttribute('data-id'))
                            .then(res => res.json())
                            .then(data => {
                                fetch("<?= admin_url('admin-ajax.php') ?>?action=stockline_log_ajax_download&id=" + btn.getAttribute('data-id') + "&success=" + (data.success ? 1 : 0), {method: "GET"});
                                if(data.success && data.url) {
                                    window.location.href = data.url;
                                    btn.textContent = "Download High Resolution";
                                } else {
                                    btn.textContent = "Download Failed!";
                                }
                            });
                        });
                    }
                });
                </script>
            <?php endif; ?>
            <?php if ($tags): ?>
                <h3 class="stockline-single-tags">
                    <?php foreach ($tags as $tag): ?>
                        <span><?= esc_html($tag); ?></span>
                    <?php endforeach; ?>
                </h3>
            <?php endif; ?>
        </div>
    </article>
    <?php
    $related_products = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table WHERE id != %d ORDER BY id DESC LIMIT 10", $product_id)
    );
    ?>
    <?php if (!empty($related_products)): ?>
        <section class="stockline-single-related-block">
            <div class="stockline-single-related-list">
                <?php foreach ($related_products as $prod): ?>
                    <div class="stockline-single-related-item">
                        <a href="<?= esc_url(stockline_product_url($prod)) ?>">
                            <img src="<?= esc_url($prod['img_url']) ?>" alt="<?= esc_attr($prod['title']) ?>">
                            <div class="stockline-single-related-item-title"><?= esc_html($prod['title']) ?></div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>
<?php get_footer(); ?>