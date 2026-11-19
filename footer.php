<footer class="kaki">
  <div class="wadah">
    <p>Lost&amp;Found Udayana. Pusat laporan barang hilang dan ditemukan untuk lingkungan Universitas Udayana.</p>
  </div>
</footer>
<script>

document.querySelectorAll('[data-konfirmasi]').forEach(function (el) {
  el.addEventListener('click', function (ev) {
    if (!confirm(el.dataset.konfirmasi)) { ev.preventDefault(); }
  });
});

var chat = document.querySelector('.chat');
if (chat) { chat.scrollTop = chat.scrollHeight; }
</script>
</body>
</html>
