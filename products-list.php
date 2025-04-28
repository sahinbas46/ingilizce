<?php
global $wpdb;
$table = $wpdb->prefix.'stockline_products';
$rows = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC", ARRAY_A);

function stockline_product_url($row) {
    $slug = isset($row['slug']) && $row['slug'] ? $row['slug'] : sanitize_title($row['title']);
    return site_url('/images/product/'.$slug.'-'.$row['id']);
}
?>
<h2>My Products</h2>
<form method="post" action="admin.php?page=stockline-products" id="stockline-search-form" style="margin-bottom:15px;">
    <input type="text" name="search" id="stockline-search" placeholder="Search by keyword..." value="<?= isset($_POST['search']) ? esc_attr($_POST['search']) : '' ?>">
    <button type="submit" class="button">Search</button>
</form>
<form method="post" action="admin.php?page=stockline-products" id="stockline-collection-form">
    <table class="widefat striped" id="stockline-products-table">
        <thead>
            <tr>
                <th><input type="checkbox" id="stockline-select-all"></th>
                <th>Title</th>
                <th>Tags</th>
                <th>Preview</th>
                <th>Original Download</th>
                <th>Edit / Delete</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $search = isset($_POST['search']) ? trim($_POST['search']) : '';
            foreach ($rows as $row):
                if ($search && stripos($row['title'] . $row['tags'], $search) === false) continue;
            ?>
                <tr>
                    <td><input type="checkbox" name="stockline_products[]" value="<?= $row['id'] ?>"></td>
                    <td>
                        <a href="<?= esc_url(stockline_product_url($row)) ?>" target="_blank">
                            <?= esc_html($row['title']) ?>
                        </a>
                    </td>
                    <td><?= esc_html($row['tags']) ?></td>
                    <td>
                        <a href="<?= esc_url($row['img_url']) ?>" target="_blank">
                            <img src="<?= esc_url($row['img_url']) ?>" style="max-height:60px;max-width:100px;">
                        </a>
                    </td>
                    <td>
                        <?php if (!empty($row['dropbox_path'])): ?>
                            <a href="#" class="stockline-admin-download" data-id="<?= $row['id'] ?>">Original Download</a>
                        <?php else: ?>
                            None
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="admin.php?page=stockline-product-edit&id=<?= $row['id'] ?>">Edit</a> |
                        <a href="admin.php?page=stockline-products&delete=<?= $row['id'] ?>"
                           onclick="return confirm('Are you sure you want to delete?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div style="margin-top: 15px;">
        <input type="text" name="collection_name" placeholder="Enter collection name" required>
        <button type="submit" name="stockline_create_collection" class="button button-primary">Create Collection with Selected Products</button>
    </div>
</form>
<script>
document.getElementById('stockline-select-all').addEventListener('change', function() {
    var checkboxes = document.querySelectorAll('#stockline-products-table tbody input[type=checkbox]');
    for (var i=0;i<checkboxes.length;i++) checkboxes[i].checked=this.checked;
});
document.querySelectorAll('.stockline-admin-download').forEach(function(btn){
    btn.addEventListener('click', function(e){
        e.preventDefault();
        var id = btn.getAttribute('data-id');
        btn.textContent = 'Preparing...';
        fetch("<?= admin_url('admin-ajax.php') ?>?action=stockline_get_dropbox_link&id=" + id)
        .then(res => res.json())
        .then(data => {
            if(data.success && data.url) {
                window.open(data.url, "_blank");
                btn.textContent = 'Original Download';
            } else {
                btn.textContent = 'Download Failed!';
            }
        });
    });
});
</script>
<?php
if (isset($_POST['stockline_create_collection']) && !empty($_POST['stockline_products']) && !empty($_POST['collection_name'])) {
    $collection_name = sanitize_text_field($_POST['collection_name']);
    $product_ids = array_map('intval', $_POST['stockline_products']);
    $slug = sanitize_title($collection_name) . '-' . wp_generate_password(5, false, false);

    $wpdb->insert($wpdb->prefix.'stockline_collections', [
        'name' => $collection_name,
        'slug' => $slug
    ]);
    $coll_id = $wpdb->insert_id;

    foreach ($product_ids as $pid) {
        $wpdb->insert($wpdb->prefix.'stockline_collection_items', [
            'collection_id' => $coll_id,
            'product_id' => $pid
        ]);
    }
    $shortcode = urlencode('[stockline_collection_metro id="'.$coll_id.'"]');
    echo '<meta http-equiv="refresh" content="0;URL=admin.php?page=stockline-collections&shortcode='.$shortcode.'">';
    exit;
}
?>