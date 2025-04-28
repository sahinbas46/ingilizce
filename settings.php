<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if (isset($_POST['stockline_save_settings'])) {
    update_option('stockline_dropbox_app_key', sanitize_text_field($_POST['dropbox_app_key']));
    update_option('stockline_dropbox_app_secret', sanitize_text_field($_POST['dropbox_app_secret']));
    update_option('stockline_dropbox_access_token', sanitize_text_field($_POST['dropbox_access_token']));
    update_option('stockline_vision_api_key', sanitize_text_field($_POST['vision_api_key']));
    echo '<div class="updated"><p>Settings saved.</p></div>';
}
$dropbox_app_key = get_option('stockline_dropbox_app_key', '');
$dropbox_app_secret = get_option('stockline_dropbox_app_secret', '');
$dropbox_access_token = get_option('stockline_dropbox_access_token', '');
$vision_api_key = get_option('stockline_vision_api_key', '');
?>
<div class="wrap">
    <h2>Stockline Settings</h2>
    <form method="post">
        <table class="form-table">
            <tr>
                <th scope="row">Dropbox App Key</th>
                <td><input type="text" name="dropbox_app_key" value="<?php echo esc_attr($dropbox_app_key); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row">Dropbox App Secret</th>
                <td><input type="text" name="dropbox_app_secret" value="<?php echo esc_attr($dropbox_app_secret); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row">Dropbox Access Token</th>
                <td><input type="text" name="dropbox_access_token" value="<?php echo esc_attr($dropbox_access_token); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row">Google Vision API Key</th>
                <td>
                    <input type="text" name="vision_api_key" value="<?php echo esc_attr($vision_api_key); ?>" class="regular-text" />
                    <div style="color:#777;font-size:0.96em;">Enter your Google Cloud Vision API key here for server-side Vision calls.</div>
                </td>
            </tr>
        </table>
        <p>
            <input type="submit" name="stockline_save_settings" class="button button-primary" value="Save">
        </p>
    </form>
    <div style="margin-top:16px;max-width:600px;">
        <strong>How to get Dropbox App Keys?</strong>
        <ol>
            <li>Create a new app at <a href="https://www.dropbox.com/developers/apps" target="_blank">Dropbox App Console</a>.</li>
            <li>Grant <b>Full Dropbox</b> or <b>App folder</b> access.</li>
            <li>Copy App Key and App Secret, paste here.</li>
            <li>Generate an Access Token and paste here.</li>
        </ol>
        <hr>
        <strong>How to get Google Vision API Key?</strong>
        <ol>
            <li>Enable <a href="https://console.cloud.google.com/apis/library/vision.googleapis.com" target="_blank">Google Cloud Vision API</a>.</li>
            <li>Create an API key (<a href="https://console.cloud.google.com/apis/credentials" target="_blank">API Credentials</a>).</li>
            <li>Paste your API key here.</li>
        </ol>
    </div>
</div>