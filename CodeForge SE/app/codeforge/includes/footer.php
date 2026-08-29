        </div>
        <footer class="app-footer"><span>CodeForge 2.0 · Optimized Plain-PHP Build</span><span>PDO · MariaDB · No framework</span></footer>
    </main>
</div>
<script src="<?= e(asset_url('assets/js/app.js')) ?>"></script>
<?php foreach (($pageScripts ?? []) as $script): ?><script src="<?= e(asset_url($script)) ?>"></script><?php endforeach; ?>
</body>
</html>
