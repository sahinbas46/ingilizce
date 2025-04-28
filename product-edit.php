<?php
global $wpdb;
$table = $wpdb->prefix.'stockline_products';
$id = intval($_GET['id']);
$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $id), ARRAY_A);
if (!$row) { echo "Product not found."; return; }
?>
<h2>Edit Product</h2>
<form method="post" action="<?= admin_url('admin-post.php') ?>">
    <input type="hidden" name="action" value="stockline_product_edit">
    <input type="hidden" name="id" value="<?= $row['id'] ?>">
    <table class="form-table">
        <tr>
            <th>Title</th>
            <td><input type="text" name="title" value="<?= esc_attr($row['title']) ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th>Description</th>
            <td><textarea name="desc_text" rows="3" class="large-text"><?= esc_textarea($row['desc_text']) ?></textarea></td>
        </tr>
        <tr>
            <th>Tags</th>
            <td><input type="text" name="tags" value="<?= esc_attr($row['tags']) ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th>Preview</th>
            <td>
                <img src="<?= esc_url($row['img_url']) ?>" style="max-width:200px;">
            </td>
        </tr>
        <tr>
            <th>Original Download</th>
            <td>
                <?php if (!empty($row['dropbox_original_url'])): ?>
                    <a href="<?= esc_url($row['dropbox_original_url']) ?>" target="_blank">Original File</a>
                <?php else: ?>
                    None
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <?php submit_button('Save'); ?>
</form>