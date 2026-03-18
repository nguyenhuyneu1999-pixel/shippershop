<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Test Sheet</title></head>
<body>
<h3>Test openPostSheet</h3>
<button onclick="openPostSheet(549)">Test Ghi chú (post 549)</button>
<div id="result"></div>

<script>
// Check if we can load index.html JS
try {
    document.getElementById('result').innerHTML = '<p>Button ready. Check console for errors.</p>';
} catch(e) {
    document.getElementById('result').innerHTML = '<p style="color:red">Error: ' + e.message + '</p>';
}
</script>

<script>
// Simulate the same setup as index.html
var sheetPostId = 0;
function openPostSheet(pid) {
    document.getElementById('result').innerHTML += '<p>openPostSheet(' + pid + ') called!</p>';
    // Try fetch
    fetch('/api/posts.php?id=' + pid)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            document.getElementById('result').innerHTML += '<p>API response: success=' + d.success + '</p>';
            if (d.data) {
                document.getElementById('result').innerHTML += '<p>Post: ' + (d.data.content || '').substring(0, 50) + '</p>';
            }
        })
        .catch(function(e) {
            document.getElementById('result').innerHTML += '<p style="color:red">Fetch error: ' + e.message + '</p>';
        });
}
</script>
</body></html>
