<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\NodeIterator;
use League\CommonMark\Node\StringContainerHelper;
use League\CommonMark\Parser\MarkdownParser;
use League\CommonMark\Renderer\HtmlRenderer;

/**
 * Markdown to HTML: raw HTML stripped, unsafe links dropped, headings carry
 * slug ids, relative page and image links point into the handbook routes.
 */
final class Renderer {

  private MarkdownParser $parser;

  private HtmlRenderer $renderer;

  /**
   * @var \Closure(string, array<string, string>, string|null): string
   */
  private \Closure $url;

  /**
   * @param (\Closure(string, array<string, string>, string|null): string)|null $url
   *   Builds a Civi URL from path, query and fragment.
   */
  public function __construct(?\Closure $url = NULL) {
    $environment = new Environment([
      'html_input' => 'strip',
      'allow_unsafe_links' => FALSE,
      'heading_permalink' => [
        'insert' => 'none',
        'apply_id_to_heading' => TRUE,
        'id_prefix' => '',
        'fragment_prefix' => '',
      ],
      // A focusable scroll box, so wide tables scroll on narrow screens and by keyboard.
      'table' => [
        'wrap' => [
          'enabled' => TRUE,
          'tag' => 'div',
          'attributes' => ['class' => 'handbook-table', 'tabindex' => '0'],
        ],
      ],
    ]);
    $environment->addExtension(new CommonMarkCoreExtension());
    $environment->addExtension(new GithubFlavoredMarkdownExtension());
    $environment->addExtension(new HeadingPermalinkExtension());
    $this->parser = new MarkdownParser($environment);
    $this->renderer = new HtmlRenderer($environment);
    $this->url = $url
    ?? static fn (string $path, array $query, ?string $fragment): string => \CRM_Utils_System::url(
      $path,
      $query,
      FALSE,
      $fragment,
      FALSE,
    );
  }

  /**
   * @return array{html: string, headings: list<array{level: int, text: string, slug: string, line: int}>}
   */
  public function render(string $source, string $markdown): array {
    $document = $this->parser->parse($markdown);
    $this->rewriteLinks($document, $source);
    return [
      'html' => (string) $this->renderer->renderDocument($document),
      'headings' => self::headings($document),
    ];
  }

  /**
   * @return list<array{level: int, text: string, slug: string, line: int}>
   */
  private static function headings(Document $document): array {
    $headings = [];
    foreach ($document->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
      if (!$node instanceof Heading) {
        continue;
      }
      $id = $node->data->get('attributes/id', '');
      $headings[] = [
        'level' => $node->getLevel(),
        'text' => StringContainerHelper::getChildText($node),
        'slug' => is_string($id) ? $id : '',
        'line' => (int) $node->getStartLine(),
      ];
    }
    return $headings;
  }

  private function rewriteLinks(Document $document, string $source): void {
    foreach ($document->iterator() as $node) {
      if (!$node instanceof Image && !$node instanceof Link) {
        continue;
      }
      $node->setUrl(
        $node instanceof Image ? $this->imageUrl($source, $node->getUrl()) : $this->linkUrl($source, $node->getUrl()),
      );
    }
  }

  private static function isRelative(string $url): bool {
    return (
      $url !== ''
      && !str_starts_with($url, '/')
      && !str_starts_with($url, '#')
      && preg_match('/^[a-z][a-z0-9+.-]*:/i', $url) !== 1
    );
  }

  private function linkUrl(string $source, string $url): string {
    if (!self::isRelative($url)) {
      return $url;
    }
    [$path, $fragment] = array_pad(explode('#', $url, 2), 2, NULL);
    // The page route takes no query of its own; ?… after the filename is dropped.
    $path = explode('?', (string) $path, 2)[0];
    if (str_ends_with($path, '.md')) {
      // A link to a language variant names the page: events.de.md is events.
      $slug = PageName::slug(basename($path));
      return ($this->url)('civicrm/handbook', ['p' => $source . '/' . $slug], $fragment);
    }
    return $this->imageUrl($source, $url);
  }

  private function imageUrl(string $source, string $url): string {
    if (!self::isRelative($url)) {
      return $url;
    }
    $path = preg_replace('#^(\./)+#', '', $url) ?? $url;
    return ($this->url)('civicrm/handbook/asset', ['p' => $source . '/' . $path], NULL);
  }

}
