<?php
declare(strict_types=1);

namespace Sunset\Services;

use RuntimeException;

/** Valida, reduce y guarda las fotos de las publicaciones (pensado para fotos de celular). */
final class ImageUploader
{
    private const MAX_BYTES = 12 * 1024 * 1024;   // 12 MB
    private const MAX_SIDE  = 1280;               // lado mayor, en píxeles
    private const DIR       = 'uploads/posts';
    private const ALLOWED   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    /** @return string ruta relativa a public/, ej: uploads/posts/ab12.jpg
     *  @throws RuntimeException con un mensaje apto para mostrar al usuario */
    public static function savePostImage(array $file): string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('La foto es demasiado pesada. Elige otra de menos de 12 MB.');
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            throw new RuntimeException('No se pudo subir la foto. Inténtalo de nuevo.');
        }
        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new RuntimeException('La foto es demasiado pesada. Elige otra de menos de 12 MB.');
        }

        $tmp  = (string) $file['tmp_name'];
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
        if (!isset(self::ALLOWED[$mime]) || @getimagesize($tmp) === false) {
            throw new RuntimeException('Formato no válido. Usa una foto JPG, PNG o WebP.');
        }

        $dir = ROOT_PATH . '/public/' . self::DIR;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('No se pudo guardar la foto en el servidor.');
        }
        $name = bin2hex(random_bytes(12));

        // Sin la extensión GD se guarda tal cual (ya validada).
        if (!function_exists('imagecreatetruecolor')) {
            $relative = self::DIR . '/' . $name . '.' . self::ALLOWED[$mime];
            if (!move_uploaded_file($tmp, ROOT_PATH . '/public/' . $relative)) {
                throw new RuntimeException('No se pudo guardar la foto en el servidor.');
            }
            return $relative;
        }

        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmp),
            'image/png'  => @imagecreatefrompng($tmp),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
            default      => false,
        };
        if ($source === false) {
            throw new RuntimeException('No se pudo procesar la foto. Prueba con otra.');
        }

        // Las fotos de celular traen la orientación en los metadatos: se aplica antes de descartarlos.
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $orientation = (int) (@exif_read_data($tmp)['Orientation'] ?? 1);
            $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
            if ($angle !== 0 && ($rotated = imagerotate($source, $angle, 0)) !== false) {
                $source = $rotated;
            }
        }

        $w = imagesx($source);
        $h = imagesy($source);
        $scale = min(1, self::MAX_SIDE / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $out = imagecreatetruecolor($nw, $nh);
        imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));   // fondo blanco para PNG con transparencia
        imagecopyresampled($out, $source, 0, 0, 0, 0, $nw, $nh, $w, $h);

        $relative = self::DIR . '/' . $name . '.jpg';
        $saved = imagejpeg($out, ROOT_PATH . '/public/' . $relative, 82);
        imagedestroy($source);
        imagedestroy($out);

        if (!$saved) {
            throw new RuntimeException('No se pudo guardar la foto en el servidor.');
        }
        return $relative;
    }

    /** Borra una foto previamente guardada (solo dentro de uploads/posts). */
    public static function delete(string $relativePath): void
    {
        if (preg_match('#^' . preg_quote(self::DIR, '#') . '/[a-f0-9]{24}\.(jpg|png|webp)$#', $relativePath)) {
            @unlink(ROOT_PATH . '/public/' . $relativePath);
        }
    }
}
