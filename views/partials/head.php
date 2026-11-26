<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Sunset') ?> · Sunset</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Merriweather:ital@0;1&family=Open+Sans:wght@700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<?php /* ?v=fecha de modificación: obliga al navegador a descargar el CSS nuevo cuando cambia */
$cssFile = ROOT_PATH . '/public/css/sunset.css'; ?>
<link href="<?= e(asset('css/sunset.css') . '?v=' . (is_file($cssFile) ? filemtime($cssFile) : '1')) ?>" rel="stylesheet">
