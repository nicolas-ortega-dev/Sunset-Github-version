<?php
declare(strict_types=1);

/** Escapa texto para HTML. Úsalo en TODO dato que se imprima en una vista. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Carpeta pública desde la raíz web, ej: /sunset/public */
function base_url(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    return rtrim($dir, '/');
}

function asset(string $path): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function url(string $page = 'home', array $query = []): string
{
    return base_url() . '/index.php?' . http_build_query(['page' => $page] + $query);
}

function redirect(string $page, array $query = []): never
{
    header('Location: ' . url($page, $query));
    exit;
}

/** Lee un campo POST como texto (ignora arreglos o valores raros). */
function input(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** HTML de 5 estrellas con $rating llenas. */
function stars(int $rating): string
{
    $rating = max(0, min(5, $rating));
    $out = '<span class="stars" role="img" aria-label="Puntuación: ' . $rating . ' de 5">';
    for ($i = 1; $i <= 5; $i++) {
        $out .= '<i class="bi ' . ($i <= $rating ? 'bi-star-fill' : 'bi-star') . '" aria-hidden="true"></i>';
    }
    return $out . '</span>';
}

/** "justo ahora", "hace 5 min", "hace 3 h" o dd/mm/aaaa. */
function format_date(?string $timestamp): string
{
    $time = $timestamp ? strtotime($timestamp) : false;
    if ($time === false) {
        return '';
    }
    $diff = time() - $time;
    return match (true) {
        $diff < 60    => 'justo ahora',
        $diff < 3600  => 'hace ' . intdiv($diff, 60) . ' min',
        $diff < 86400 => 'hace ' . intdiv($diff, 3600) . ' h',
        default       => date('d/m/Y', $time),
    };
}

function full_date(?string $timestamp): string
{
    $time = $timestamp ? strtotime($timestamp) : false;
    return $time === false ? '' : date('d/m/Y H:i', $time);
}
