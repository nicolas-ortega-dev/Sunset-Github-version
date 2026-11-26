<?php
declare(strict_types=1);

namespace Sunset\Services;

use RuntimeException;
use Sunset\Core\Config;

/**
 * Único punto de contacto con la API de Open Library.
 * Si algún día cambia la API, solo se toca esta clase.
 */
final class OpenLibraryService
{
    private const BASE = 'https://openlibrary.org';

    /** Campos de la obra y, aparte, los de la edición que mejor coincide con la búsqueda (ej: la española). */
    private const WORK_FIELDS    = 'key,title,author_name,first_publish_year,edition_count,isbn,subject,cover_i,language';
    private const EDITION_FIELDS = ',editions,editions.key,editions.title,editions.language,editions.cover_i,editions.isbn';

    private static float $lastRequest = 0.0;
    private static bool $withEditions = true;

    /** false si Open Library rechazó pedir datos de edición (se vuelve a datos de la obra). */
    public static function usingEditionData(): bool
    {
        return self::$withEditions;
    }

    /**
     * @param int $timeout  segundos máximos por petición
     * @param int $attempts intentos ante fallos de red (usar 1 en páginas web para no hacer esperar al usuario)
     */
    public function __construct(
        private int $timeout = 30,
        private int $attempts = 3,
    ) {
    }

    /** Busca obras. Devuelve la lista 'docs' de Open Library. */
    public function search(string $query, int $page = 1, int $limit = 100, ?string $sort = null): array
    {
        $params = [
            'q'      => $query,
            'page'   => max(1, $page),
            'limit'  => min(100, max(1, $limit)),
            'fields' => self::WORK_FIELDS . (self::$withEditions ? self::EDITION_FIELDS : ''),
        ];
        if ($sort !== null) {
            $params['sort'] = $sort;
        }
        try {
            $data = $this->get('/search.json?' . http_build_query($params));
        } catch (RuntimeException $e) {
            // Error 4xx sin 'sort': lo más probable es que no acepte los campos de edición.
            if (self::$withEditions && $sort === null && $e->getCode() >= 400 && $e->getCode() < 500) {
                self::$withEditions = false;
                return $this->search($query, $page, $limit, null);
            }
            throw $e;
        }
        return is_array($data['docs'] ?? null) ? $data['docs'] : [];
    }

    /** Datos completos de una obra (sinopsis). null si no existe. $workKey ej: OL45883W */
    public function work(string $workKey): ?array
    {
        if (!preg_match('/^OL\d+W$/', $workKey)) {
            return null;
        }
        return $this->get('/works/' . $workKey . '.json', true);
    }

    public static function coverUrl(int $coverId, string $size = 'M'): string
    {
        $size = in_array($size, ['S', 'M', 'L'], true) ? $size : 'M';
        return 'https://covers.openlibrary.org/b/id/' . $coverId . '-' . $size . '.jpg';
    }

    public static function workUrl(string $workKey): string
    {
        return self::BASE . '/works/' . rawurlencode($workKey);
    }

    // ------------------------------------------------------------------

    private function get(string $path, bool $nullOn404 = false): ?array
    {
        $attempt = 0;
        while (true) {
            $attempt++;
            $this->throttle();

            [$body, $errno, $error, $status] = $this->fetch(self::BASE . $path);

            if ($errno === 0 && $status === 200 && is_string($body)) {
                $data = json_decode($body, true);
                if (!is_array($data)) {
                    throw new RuntimeException('Open Library devolvió una respuesta que no es JSON.');
                }
                return $data;
            }

            if ($errno === 0 && $status === 404 && $nullOn404) {
                return null;
            }

            if ($errno === 60) { // certificado SSL no verificable (típico de XAMPP en Windows)
                throw new RuntimeException(
                    'Error de certificado SSL (60). En config/config.php pon openlibrary.ssl_verify en false.'
                );
            }

            $retryable = $errno !== 0 || $status === 429 || $status >= 500;
            if ($retryable && $attempt < $this->attempts) {
                sleep($status === 429 ? 5 * $attempt : 2 * $attempt);
                continue;
            }

            throw new RuntimeException(
                $errno !== 0
                    ? "No se pudo conectar con Open Library: $error"
                    : "Open Library respondió con el código HTTP $status.",
                $errno !== 0 ? 0 : $status   // el código del error es el HTTP (0 = fallo de red)
            );
        }
    }

    /**
     * Hace la petición HTTP. Usa cURL si está disponible y, si no, los streams de PHP.
     * @return array [cuerpo|false, errno (0 = sin fallo de red), mensaje de error, código HTTP]
     */
    private function fetch(string $url): array
    {
        $userAgent = (string) Config::get('openlibrary.user_agent', 'Sunset/1.0');
        $verifySsl = (bool) Config::get('openlibrary.ssl_verify', true);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_TIMEOUT        => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => min(10, $this->timeout),
                CURLOPT_USERAGENT      => $userAgent,
                CURLOPT_HTTPHEADER     => ['Accept: application/json'],
                CURLOPT_SSL_VERIFYPEER => $verifySsl,
            ]);
            $body   = curl_exec($ch);
            $errno  = curl_errno($ch);
            $error  = curl_error($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
            return [$body, $errno, $error, $status];
        }

        // Alternativa sin cURL (requiere allow_url_fopen y la extensión openssl, ambas activas en XAMPP).
        $context = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'timeout'       => $this->timeout,
                'ignore_errors' => true,      // así se puede leer el código HTTP aunque sea 4xx/5xx
                'header'        => "Accept: application/json\r\nUser-Agent: $userAgent\r\n",
            ],
            'ssl' => ['verify_peer' => $verifySsl, 'verify_peer_name' => $verifySsl],
        ]);
        $body = @file_get_contents($url, false, $context);

        $status = 0;
        foreach ($http_response_header ?? [] as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
                $status = (int) $m[1];      // con redirecciones queda el último
            }
        }
        if ($body === false && $status === 0) {
            return [false, 1, 'sin respuesta (revisa internet, allow_url_fopen y la extensión openssl)', 0];
        }
        return [$body, 0, '', $status];
    }

    /** Pausa mínima entre peticiones para no sobrecargar la API. */
    private function throttle(): void
    {
        $gap  = ((int) Config::get('openlibrary.delay_ms', 400)) / 1000;
        $wait = self::$lastRequest + $gap - microtime(true);
        if ($wait > 0) {
            usleep((int) ($wait * 1_000_000));
        }
        self::$lastRequest = microtime(true);
    }
}
