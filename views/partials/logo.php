<?php /* Si existe public/img/logo.png se usa tu logo; si no, un logo de texto. */
$logoFile = ROOT_PATH . '/public/img/logo.png'; ?>
<a class="sunset-logo" href="<?= e(url('home')) ?>" aria-label="Sunset, inicio">
<?php if (is_file($logoFile)): ?>
    <img src="<?= e(asset('img/logo.png')) ?>" alt="Sunset">
<?php else: ?>
    <span class="sunset-logo-text">SUNSET</span>
    <svg class="sunset-logo-mark" viewBox="0 0 64 48" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="32" cy="9" r="4.5"/>
        <path d="M32 .5v2M32 15.5v2M23.5 9h2M38.5 9h2M26 3l1.5 1.5M36.5 13.5L38 15M38 3l-1.5 1.5M27.5 13.5L26 15"/>
        <path d="M32 24c-5-3-13-3-20-1v21c7-2 15-2 20 1 5-3 13-3 20-1V23c-7-2-15-2-20 1z"/>
        <path d="M32 24v21"/>
    </svg>
<?php endif; ?>
</a>
