<?php

declare(strict_types = 1);

namespace Civi\Handbook;

use Civi\Handbook\Markdown\Video;
use Civi\Handbook\Markdown\VideoRenderer;
use Civi\Handbook\Vendor\League\CommonMark\Environment\Environment;
use Civi\Handbook\Vendor\League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use Civi\Handbook\Vendor\League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use Civi\Handbook\Vendor\League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use Civi\Handbook\Vendor\League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use Civi\Handbook\Vendor\League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use Civi\Handbook\Vendor\League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use Civi\Handbook\Vendor\League\CommonMark\Node\Block\Document;
use Civi\Handbook\Vendor\League\CommonMark\Node\NodeIterator;
use Civi\Handbook\Vendor\League\CommonMark\Node\StringContainerHelper;
use Civi\Handbook\Vendor\League\CommonMark\Parser\MarkdownParser;
use Civi\Handbook\Vendor\League\CommonMark\Renderer\HtmlRenderer;

/**
 * Markdown to HTML: raw HTML stripped, unsafe links dropped, headings carry
 * slug ids, relative page and image links point into the handbook routes, and
 * a poster image linked to an https video file plays in place.
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
    $environment->addRenderer(Video::class, new VideoRenderer());
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
   * @param string $dir
   *   Id of the page's directory (Page::dir()); relative links resolve against it.
   *
   * @return array{html: string, headings: list<array{level: int, text: string, slug: string, line: int}>}
   */
  public function render(string $dir, string $markdown): array {
    $document = $this->parser->parse($markdown);
    $this->rewriteLinks($document, $dir);
    VideoEmbedder::embed($document);
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

  private function rewriteLinks(Document $document, string $dir): void {
    foreach ($document->iterator() as $node) {
      if (!$node instanceof Image && !$node instanceof Link) {
        continue;
      }
      $node->setUrl(
        $node instanceof Image ? $this->imageUrl($dir, $node->getUrl()) : $this->linkUrl($dir, $node->getUrl()),
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

  private function linkUrl(string $dir, string $url): string {
    if (!self::isRelative($url)) {
      return $url;
    }
    [$path, $fragment] = array_pad(explode('#', $url, 2), 2, NULL);
    // The page route takes no query of its own; ?… after the filename is dropped.
    $path = explode('?', (string) $path, 2)[0];
    if (str_ends_with($path, '.md')) {
      // A link to a language variant names the page: events.de.md is events.
      $slug = PageName::slug(basename($path));
      return ($this->url)(
        'civicrm/handbook',
        ['p' => PagePath::resolve($dir, dirname($path) . '/' . $slug)],
        $fragment,
      );
    }
    return $this->imageUrl($dir, $url);
  }

  private function imageUrl(string $dir, string $url): string {
    if (!self::isRelative($url)) {
      return $url;
    }
    return ($this->url)('civicrm/handbook/asset', ['p' => PagePath::resolve($dir, $url)], NULL);
  }

}
