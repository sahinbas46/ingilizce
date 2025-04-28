<?php
/*
Plugin Name: Stockline Dropbox Gallery & Collections
Description: Automatic product gallery from Dropbox folder. Modern single product template.
Version: 4.1
Author: sahinbas46
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define('STOCKLINE_PATH', plugin_dir_path(__FILE__));

// SEO-friendly product URLs
add_action('init', function() {
    add_rewrite_rule('^images/product/(.+)-([0-9]+)/?$', 'index.php?stockline_product_id=$matches[2]', 'top');
    add_rewrite_tag('%stockline_product_id%', '([0-9]+)');
});
add_filter('template_include', function($template) {
    if (get_query_var('stockline_product_id')) {
        return STOCKLINE_PATH . 'includes/stockline-product-template.php';
    }
    return $template;
});

register_activation_hook(__FILE__, function() {
    stockline_maybe_upgrade_db();
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

function stockline_maybe_upgrade_db() {
    global $wpdb;
    $products = $wpdb->prefix.'stockline_products';
    $collections = $wpdb->prefix.'stockline_collections';
    $coll_items = $wpdb->prefix.'stockline_collection_items';
    $charset_collate = $wpdb->get_charset_collate();
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta("CREATE TABLE $products (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        title VARCHAR(191),
        slug VARCHAR(191),
        desc_text TEXT,
        tags VARCHAR(191),
        img_url TEXT,
        dropbox_path TEXT,
        dropbox_original_url TEXT,
        img_width INT(11) DEFAULT 0,
        img_height INT(11) DEFAULT 0,
        img_size INT(11) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY slug (slug)
    ) $charset_collate;");

    dbDelta("CREATE TABLE $collections (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(191) NOT NULL,
        slug VARCHAR(191) NOT NULL UNIQUE,
        PRIMARY KEY  (id)
    ) $charset_collate;");

    dbDelta("CREATE TABLE $coll_items (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        collection_id BIGINT(20) UNSIGNED NOT NULL,
        product_id BIGINT(20) UNSIGNED NOT NULL,
        PRIMARY KEY  (id),
        KEY collection_idx (collection_id),
        KEY product_idx (product_id)
    ) $charset_collate;");

    // Auto slug fill
    $rows = $wpdb->get_results("SELECT id, title, slug FROM $products", ARRAY_A);
    foreach ($rows as $row) {
        $clean_slug = sanitize_title($row['title']);
        if ($row['slug'] !== $clean_slug) {
            $wpdb->update($products, ['slug'=>$clean_slug], ['id'=>$row['id']]);
        }
    }
}
add_action('admin_init', 'stockline_maybe_upgrade_db');

// Admin menu
add_action('admin_menu', 'stockline_admin_menu');
function stockline_admin_menu() {
    add_menu_page('Stockline', 'Stockline', 'manage_options', 'stockline', 'stockline_dashboard_page', 'dashicons-images-alt2');
    add_submenu_page('stockline','My Products','My Products','manage_options','stockline-products','stockline_products_page');
    add_submenu_page('stockline','Add Product','Add Product','manage_options','stockline-product-add','stockline_product_add_page');
    add_submenu_page('stockline','Collections','Collections','manage_options','stockline-collections','stockline_collections_page');
    add_submenu_page('stockline','Settings','Settings','manage_options','stockline-settings','stockline_settings_page');
    add_submenu_page('stockline','Edit Product','','manage_options','stockline-product-edit','stockline_product_edit_page');
}
function stockline_dashboard_page() { echo '<h2>Stockline Gallery</h2>'; }
function stockline_products_page() { require_once STOCKLINE_PATH . 'admin/products-list.php'; }
function stockline_product_add_page() { require_once STOCKLINE_PATH . 'admin/product-add.php'; }
function stockline_collections_page() { require_once STOCKLINE_PATH . 'admin/collections.php'; }
function stockline_settings_page() { require_once STOCKLINE_PATH . 'admin/settings.php'; }
function stockline_product_edit_page() { require_once STOCKLINE_PATH . 'admin/product-edit.php'; }

function stockline_download_image($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; StocklineBot/1.0)');
        $data = curl_exec($ch);
        curl_close($ch);
        if ($data !== false) return $data;
    }
    return @file_get_contents($url);
}

require_once STOCKLINE_PATH . 'includes/vision-helper.php';

// Dropbox bulk import (AJAX)
add_action('wp_ajax_stockline_dropbox_import', 'stockline_dropbox_import_callback');
function stockline_dropbox_import_callback() {
    if (!current_user_can('manage_options')) die(json_encode(['success'=>false,'msg'=>'Unauthorized access.']));
    global $wpdb;
    $table = $wpdb->prefix.'stockline_products';

    $link = trim($_POST['dropbox_folder_link'] ?? '');
    if (!$link) die(json_encode(['success'=>false,'msg'=>'Dropbox folder link is empty!']));
    $dropbox_app_key = get_option('stockline_dropbox_app_key', '');
    $dropbox_app_secret = get_option('stockline_dropbox_app_secret', '');
    $access_token = get_option('stockline_dropbox_access_token', '');
    if (!$access_token || !$dropbox_app_key || !$dropbox_app_secret) 
        die(json_encode(['success'=>false,'msg'=>'Dropbox App Key/Secret/Access Token missing! Please fill in the settings.']));

    if (!preg_match('~/scl/fo/([a-zA-Z0-9]+)/~', $link)) {
        die(json_encode(['success'=>false,'msg'=>'Link format error!']));
    }

    // Dropbox folder meta
    $meta_resp = stockline_dropbox_post("https://api.dropboxapi.com/2/sharing/get_shared_link_metadata", 
        ["url"=>$link], $access_token);
    $meta = json_decode($meta_resp, true);
    $dropbox_path = (isset($meta['.tag']) && $meta['.tag']==='folder') ? "" : ($meta['path_lower'] ?? '');
    if ($dropbox_path===null) die(json_encode(['success'=>false,'msg'=>'Dropbox folder not found!']));

    // List files
    $list_resp = stockline_dropbox_post("https://api.dropboxapi.com/2/files/list_folder", [
        "path"=>$dropbox_path,
        "recursive"=>false,
        "include_media_info"=>true,
        "shared_link"=>["url"=>$link]
    ], $access_token);
    $list = json_decode($list_resp, true);
    if (empty($list['entries'])) die(json_encode(['success'=>false,'msg'=>'No files in the folder!']));

    $debug_links_file = STOCKLINE_PATH.'dropbox_debug_links.txt'; file_put_contents($debug_links_file, "");
    $added = 0; $err = 0;
    foreach($list['entries'] as $file) {
        if ($file['.tag'] !== 'file' || !preg_match('/\.(jpe?g|png|gif|webp)$/i', $file['name'])) continue;
        $dropbox_file_path = $file['path_lower'];
        $tmp_resp = stockline_dropbox_post("https://api.dropboxapi.com/2/files/get_temporary_link", 
            ["path" => $dropbox_file_path], $access_token);
        $tmp_data = json_decode($tmp_resp, true);
        if (!isset($tmp_data['link'])) {
            file_put_contents($debug_links_file, "[$file[name]][get_temporary_link error]\n$tmp_resp\n", FILE_APPEND); $err++; continue;
        }
        $img_url = $tmp_data['link'];
        file_put_contents($debug_links_file, "[$file[name]][get_temporary_link]\n$img_url\n", FILE_APPEND);

        $safe_name = strtolower(preg_replace('/[^a-zA-Z0-9\-_\.]/', '-', $file['name']));
        $img_data = stockline_download_image($img_url);
        file_put_contents($debug_links_file, "[$file[name]][downloaded data length: ".strlen($img_data)."]\n", FILE_APPEND);

        if ($img_data === false || strlen($img_data) < 1000) {
            file_put_contents($debug_links_file, "[$file[name]][downloaded file too small or empty]\n", FILE_APPEND); $err++; continue;
        }
        $upload = wp_upload_bits($safe_name, null, $img_data);
        if ($upload['error']) {
            file_put_contents($debug_links_file, "[$file[name]][wp_upload_bits error]\n".$upload['error']."\n", FILE_APPEND); $err++; continue;
        }
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        $file_array = ['name' => $safe_name, 'tmp_name' => $upload['file']];
        $attach_id = media_handle_sideload($file_array, 0);
        if (is_wp_error($attach_id)) {
            file_put_contents($debug_links_file, "[$file[name]][media_handle_sideload error]\n".print_r($attach_id,true)."\n", FILE_APPEND); $err++; @unlink($upload['file']); continue;
        }
        $img_url_final = wp_get_attachment_url($attach_id);

        $image_info = @getimagesize($upload['file']);
        $img_width = $image_info ? intval($image_info[0]) : 0;
        $img_height = $image_info ? intval($image_info[1]) : 0;
        $img_size = filesize($upload['file']);

        $vision_data = stockline_get_vision_metadata($img_url_final, 'en');

        $slug = sanitize_title($vision_data['title'] ?: $file['name']);

        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE dropbox_path=%s", $dropbox_file_path));
        if ($exists) continue;

        $wpdb->insert($table, [
            'title' => $vision_data['title'] ?: $file['name'],
            'slug' => $slug,
            'desc_text' => $vision_data['desc'],
            'tags' => $vision_data['keywords'],
            'img_url' => $img_url_final,
            'dropbox_path' => $dropbox_file_path,
            'dropbox_original_url' => $tmp_data['link'],
            'img_width' => $img_width,
            'img_height' => $img_height,
            'img_size' => $img_size
        ]);
        $added++;
    }
    $msg = "$added images added.";
    if ($err > 0) $msg .= " ($err errors - see debug_links file)";
    die(json_encode(['success'=>true, 'msg'=>$msg]));
}

// Dropbox API POST helper
function stockline_dropbox_post($url, $payload, $token) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $resp = curl_exec($ch);
    curl_close($ch);
    return $resp;
}

// Live Dropbox temp link for download (AJAX) + logging
add_action('wp_ajax_stockline_get_dropbox_link', function() {
    $log_file = WP_CONTENT_DIR . '/stockline_download_log.txt';
    $log = function($msg) use ($log_file) {
        file_put_contents($log_file, date('Y-m-d H:i:s').' | '.$msg."\n", FILE_APPEND);
    };
    if(!isset($_GET['id'])) {
        $log("No ID in request");
        die(json_encode(['success'=>false]));
    }
    global $wpdb;
    $id = intval($_GET['id']);
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}stockline_products WHERE id=%d", $id), ARRAY_A);
    if(!$row || empty($row['dropbox_path'])) {
        $log("No row or dropbox_path for ID $id");
        die(json_encode(['success'=>false]));
    }

    $access_token = get_option('stockline_dropbox_access_token', '');
    if(!$access_token) {
        $log("No Dropbox access token");
        die(json_encode(['success'=>false]));
    }

    $tmp_resp = stockline_dropbox_post("https://api.dropboxapi.com/2/files/get_temporary_link",
        ["path" => $row['dropbox_path']], $access_token);
    $tmp_data = json_decode($tmp_resp, true);
    $log("Requested dropbox_path={$row['dropbox_path']} | Dropbox API response: " . $tmp_resp);
    if(isset($tmp_data['link'])) {
        $log("Success for ID $id: " . $tmp_data['link']);
        die(json_encode(['success'=>true,'url'=>$tmp_data['link']]));
    }
    $log("Failed for ID $id");
    die(json_encode(['success'=>false]));
});

// Download click log endpoint
add_action('wp_ajax_stockline_log_ajax_download', function() {
    $log_file = WP_CONTENT_DIR . '/stockline_download_log.txt';
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $success = isset($_GET['success']) ? intval($_GET['success']) : 0;
    $msg = date('Y-m-d H:i:s') . " | Download click for ID $id: " . ($success ? 'SUCCESS' : 'FAILED');
    file_put_contents($log_file, $msg."\n", FILE_APPEND);
    die('1');
});

// Delete product
add_action('admin_init', function() {
    if (isset($_GET['page'], $_GET['delete']) && $_GET['page']==='stockline-products') {
        global $wpdb;
        $id = intval($_GET['delete']);
        $wpdb->delete($wpdb->prefix.'stockline_products', ['id'=>$id]);
        wp_redirect(admin_url('admin.php?page=stockline-products&msg=deleted'));
        exit;
    }
});

// Edit product
add_action('admin_post_stockline_product_edit', function() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    global $wpdb;
    $id = intval($_POST['id']);
    $title = sanitize_text_field($_POST['title']);
    $slug = sanitize_title($title);
    $desc_text = sanitize_textarea_field($_POST['desc_text']);
    $tags = sanitize_text_field($_POST['tags']);
    $wpdb->update($wpdb->prefix.'stockline_products', [
        'title' => $title,
        'slug' => $slug,
        'desc_text' => $desc_text,
        'tags' => $tags
    ], ['id'=>$id]);
    wp_redirect(admin_url('admin.php?page=stockline-products&msg=edited'));
    exit;
});

require_once STOCKLINE_PATH . 'includes/all-products-metro-shortcode.php';