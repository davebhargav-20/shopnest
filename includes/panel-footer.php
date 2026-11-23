        </div>
    </main>
</div>
<script>
/* confirm before destructive actions: <form data-confirm="Are you sure?"> */
document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
});
</script>
</body>
</html>
