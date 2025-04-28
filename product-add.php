<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap">
    <h2>Add Products from Dropbox Folder</h2>
    <form id="stockline-add-form">
        <table class="form-table">
            <tr>
                <th scope="row">Dropbox Folder Link</th>
                <td>
                    <input type="url" name="dropbox_folder_link" id="dropbox_folder_link" style="width:400px" placeholder="https://www.dropbox.com/scl/fo/..." required />
                </td>
            </tr>
        </table>
        <p>
            <button type="submit" class="button button-primary">Add Products from Folder</button>
        </p>
    </form>
    <div id="stockline-add-results"></div>
</div>
<script>
document.getElementById('stockline-add-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var link = document.getElementById('dropbox_folder_link').value;
    document.getElementById('stockline-add-results').innerHTML = "Uploading...";
    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxurl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function(){
        try {
            var res = JSON.parse(xhr.responseText);
            if(res.success) {
                document.getElementById('stockline-add-results').innerHTML = '<span style="color:green">'+res.msg+'</span>';
                setTimeout(function(){ location.reload(); }, 2000);
            } else {
                document.getElementById('stockline-add-results').innerHTML = '<span style="color:red">'+res.msg+'</span>';
            }
        } catch(e){ document.getElementById('stockline-add-results').innerHTML = 'API/JSON error'; }
    };
    xhr.send('action=stockline_dropbox_import&dropbox_folder_link='+encodeURIComponent(link));
});
</script>