        </div>
        <footer class="app-footer"><span>CodeForge · <?= e($pageTitle ?? 'Competitive Programming') ?></span></footer>
    </main>
</div>
<script src="<?= e(asset_url('assets/js/app.js')) ?>"></script>
<?php foreach (($pageScripts ?? []) as $script): ?><script src="<?= e(asset_url($script)) ?>"></script><?php endforeach; ?>
</body>
</html>
