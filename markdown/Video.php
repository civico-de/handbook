<?php

declare(strict_types = 1);

namespace Civi\Handbook\Markdown;

use Civi\Handbook\Vendor\League\CommonMark\Node\Inline\AbstractInline;
use Civi\Handbook\Vendor\League\CommonMark\Node\StringContainerInterface;

/**
 * A linked poster image whose link is a video file, played in place. Its
 * label is its text, so headings and search keep the poster's description.
 */
final class Video extends AbstractInline implements StringContainerInterface {

  public function __construct(
    public readonly string $src,
    public readonly string $poster,
    private string $label,
  ) {
    parent::__construct();
  }

  public function getLiteral(): string {
    return $this->label;
  }

  public function setLiteral(string $literal): void {
    $this->label = $literal;
  }

}
