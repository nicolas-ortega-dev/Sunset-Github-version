<?php
declare(strict_types=1);

namespace Sunset\Core;

/** El usuario ya reseñó esa obra (solo se permite una reseña por obra). */
final class DuplicateReviewException extends \RuntimeException
{
}
