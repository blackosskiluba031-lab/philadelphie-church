        </main><!-- /.admin-content -->
    </div><!-- /.admin-main -->
</div><!-- /.admin-layout -->

<!-- Scripts JS -->
<script src="<?= ASSETS_URL ?>/js/theme-switcher.js?v=<?= APP_VERSION ?>"></script>
<script src="<?= ASSETS_URL ?>/js/app.js?v=<?= APP_VERSION ?>"></script>

<!-- Script pour le toggle de la sidebar admin sur tablette/mobile -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('admin-sidebar');
    const toggle = document.getElementById('sidebar-toggle');
    if (toggle && sidebar) {
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('open');
        });
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }
});
</script>
</body>
</html>
