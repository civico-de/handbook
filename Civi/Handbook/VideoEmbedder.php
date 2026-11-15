<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use Civi\Handbook\Markdown\Video;
use Civi\Handbook\Vendor\League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use Civi\Handbook\Vendor\League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use Civi\Handbook\Vendor\League\CommonMark\Node\Block\Document;
use Civi\Handbook\Vendor\League\CommonMark\Node\StringContainerHelper;

/**
 * Turns a link to an https video file whose only content is an image into a
 * Video node: the image is its poster, the image's text its label. A poster
 * without text stays a link, as a player needs a name.
 */
final class VideoEmbedder {

  private const VIDEO_URL = '#^https://[^/?\#]+/[^?\#]*\.(mp4|webm)([?\#].*)?$#i';

  public static function embed(Document $document): void {
    $videos = [];
    foreach ($document->iterator() as $node) {
      $poster = $node instanceof Link ? $node->firstChild() : NULL;
      if (
        !$node instanceof Link
        || !$poster instanceof Image
        || $poster->next() !== NULL
        || preg_match(self::VIDEO_URL, $node->getUrl()) !== 1
      ) {
        continue;
      }
      $label = trim(StringContainerHelper::getChildText($poster));
      if ($label !== '') {
        $videos[] = [$node, new Video($node->getUrl(), $poster->getUrl(), $label)];
      }
    }
    foreach ($videos as [$link, $video]) {
      $link->replaceWith($video);
    }
  }

}
