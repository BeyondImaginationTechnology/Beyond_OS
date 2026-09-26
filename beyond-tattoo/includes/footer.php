<script src="<?= e(bt_app_url('assets/js/app.js')) ?>?v=<?= rawurlencode((string) (@filemtime(__DIR__ . '/../assets/js/app.js') ?: '20260716')) ?>"></script>
<script>
document.querySelectorAll('form[method="post" i]').forEach(function (form) {
  if (form.querySelector('input[name="_csrf"]')) return;
  var token = document.createElement('input');
  token.type = 'hidden'; token.name = '_csrf'; token.value = <?= json_encode(bt_csrf_token()) ?>;
  form.appendChild(token);
});
</script>
</body>
</html>
