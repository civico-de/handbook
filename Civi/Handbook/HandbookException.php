<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * A request the handbook refuses; the code is the HTTP status to answer with.
 */
final class HandbookException extends \RuntimeException {

  public static function badRequest(string $message): self {
    return new self($message, 400);
  }

  public static function notFound(string $message): self {
    return new self($message, 404);
  }

}
