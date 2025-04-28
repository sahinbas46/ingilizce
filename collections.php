<?php
global $wpdb;
$table = $wpdb->prefix.'stockline_collections';
$items_table = $wpdb->prefix.'stockline_collection_items';
$product_table = $wpdb->prefix.'stockline_products';

echo '<div class="notice notice-info" style="margin-bottom:18px;"><strong>To show all products in metro layout use this Shortcode:</strong> <code>[stockline_all_products_metro]</code></div>';
if (isset($_GET['delete'])) {
    $coll_id = intval($_GET['delete']);
    $wpdb->delete($table, ['id'=>$coll_id]);
    $wpdb->delete($items_table, ['collection_id'=>$coll_id]);
    echo '<div class="notice notice-success"><p>Collection deleted.</p></div>';
}
if (isset($_GET['shortcode'])) {
    $shortcode = $_GET['shortcode'];
    echo '<div class="notice notice-success"><p>Collection created! Metro Shortcode: <code>'.esc_html($shortcode).'</code></p></div>';
}
$rows = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC", ARRAY_A);
?>
<h2>My Collections</h2>
<table class="widefat striped">
    <thead>
        <tr>
            <th>Collection Name</th>
            <th>Metro Shortcode</th>
            <th>Product Count</th>
            <th>Delete</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row):
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $items_table WHERE collection_id=%d", $row['id']));
        ?>
            <tr>
                <td><?= esc_html($row['name']) ?></td>
                <td>
                    <code>[stockline_collection_metro id="<?= $row['id'] ?>"]</code>
                </td>
                <td><?= intval($count) ?></td>
                <td>
                    <a href="admin.php?page=stockline-collections&delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>