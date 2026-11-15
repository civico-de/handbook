<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use Civi\Handbook\Vendor\League\CommonMark\Node\Node;
use Civi\Handbook\Vendor\League\CommonMark\Renderer\ChildNodeRendererInterface;
use Civi\Handbook\Vendor\League\CommonMark\Renderer\NodeRendererInterface;
use Civi\Handbook\Vendor\League\CommonMark\Util\HtmlElement;
use Civi\Handbook\Vendor\League\CommonMark\Util\Xml;

/**
 * A player that loads nothing before it is started; without video support the
 * link to the file remains.
 */
final class VideoRenderer implements NodeRendererInterface {

  public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement {
    if (!$node instanceof Video) {
      throw new \InvalidArgumentException('Not a Video node: ' . $node::class);
    }
    return new HtmlElement(
      'video',
      [
        'controls' => TRUE,
        'preload' => 'none',
        'playsinline' => TRUE,
        'poster' => $node->poster,
        'src' => $node->src,
        'aria-label' => $node->getLiteral(),
      ],
      new HtmlElement('a', ['href' => $node->src], Xml::escape($node->getLiteral())),
    );
  }

}
