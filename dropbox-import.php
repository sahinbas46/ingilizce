<h2>Dropbox ile Toplu Ürün Ekle</h2>
<form id="stockline-dropbox-import-form" method="post" action="#" onsubmit="return false;">
    <label>
        Dropbox Klasör Paylaşım Linki:<br>
        <input type="text" name="dropbox_folder_link" id="dropbox_folder_link" style="width:420px;" required>
    </label>
    <button type="submit" class="button button-primary">Başlat</button>
</form>
<div id="stockline-dropbox-import-status" style="margin-top:15px;"></div>
<script>
document.getElementById('stockline-dropbox-import-form').addEventListener('submit', function(e){
    e.preventDefault();
    var link = document.getElementById('dropbox_folder_link').value.trim();
    var statusDiv = document.getElementById('stockline-dropbox-import-status');
    statusDiv.innerHTML = 'Yükleniyor...';
    fetch(ajaxurl, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=stockline_dropbox_import&dropbox_folder_link=' + encodeURIComponent(link)
    })
    .then(r=>r.json()).then(res=>{
        statusDiv.innerHTML = res.success ? '<span style="color:green">'+res.msg+'</span>' : '<span style="color:red">'+res.msg+'</span>';
        if(res.success) setTimeout(()=>location.reload(), 1500);
    }).catch(e=>{
        statusDiv.innerHTML = 'Bir hata oluştu!';
    });
});
</script>