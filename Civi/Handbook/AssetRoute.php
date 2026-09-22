<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * civicrm/handbook/asset?p=<source>/<relpath>: an image of a manual, as a
 * PSR-7 page callback.
 */
final class AssetRoute {

  public static function handle(ServerRequestInterface $request): ResponseInterface {
    if (!Dependencies::available()) {
      return new Response(503, ['Content-Type' => 'text/plain'], Dependencies::message());
    }
    $p = $request->getQueryParams()['p'] ?? '';
    try {
      $asset = (new AssetResolver(Library::fromSystem()))->resolve(is_string($p) ? $p : '');
    }
    catch (HandbookException $e) {
      return new Response($e->getCode(), ['Content-Type' => 'text/plain'], $e->getMessage());
    }
    return new Response(
      200,
      [
        'Content-Type' => $asset['type'],
        'X-Content-Type-Options' => 'nosniff',
        'Cache-Control' => 'private, max-age=3600',
      ],
      (string) file_get_contents($asset['file']),
    );
  }

}
