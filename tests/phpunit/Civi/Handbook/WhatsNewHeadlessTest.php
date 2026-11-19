<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * @group headless
 * @covers \Civi\Handbook\WhatsNew
 * @covers \Civi\Handbook\Updates
 * @covers \Civi\Handbook\StatusCheck
 * @covers \CRM_Handbook_Page_WhatsNew
 */
final class WhatsNewHeadlessTest extends HandbookTestCase {

  public function testScreenShowsTheNewestReleaseWithALinkToAllChanges(): void {
    $info = simplexml_load_file(dirname(__DIR__, 4) . '/info.xml');
    $this->assertNotFalse($info);
    ob_start();
    try {
      (new \CRM_Handbook_Page_WhatsNew())->run();
    }
    finally {
      $html = (string) ob_get_clean();
    }
    $this->assertStringContainsString('<h2 id="handbook-release-handbook">', $html);
    $this->assertStringContainsString((string) $info->version, $html);
    $this->assertStringNotContainsString('id="handbook-unreleased"', $html);
    $this->assertMatchesRegularExpression('#<a href="[^"]*handbook%2Fchanges">All changes in #', $html);
  }

  public function testSeveralChangelogsKeepHeadingIdsApart(): void {
    $sources = [];
    foreach (['a', 'b'] as $key) {
      $dir = sys_get_temp_dir() . '/handbook-wn-' . bin2hex(random_bytes(4));
      mkdir($dir);
      file_put_contents($dir . '/index.md', "# {$key}\n");
      file_put_contents(
        $dir . '/CHANGELOG.md',
        "# Changelog\n\n## [1.0.0] - 2026-11-0" . ($key === 'a' ? '1' : '2') . "\n\n### Added\n\n- X.\n",
      );
      $sources[$key] = ['dir' => $dir, 'label' => $key, 'changelog' => $dir . '/CHANGELOG.md'];
    }
    $entries = (new WhatsNew(new Library($sources), new RenderCache(NULL)))->entries();
    $this->assertSame(['b', 'a'], array_column($entries, 'source'));
    $this->assertStringContainsString('<h3 id="b-added">', $entries[0]['html']);
    $this->assertStringContainsString('<h3 id="a-added">', $entries[1]['html']);
  }

  public function testReferenceLinksAndFragmentsKeepWorking(): void {
    $dir = sys_get_temp_dir() . '/handbook-wn-' . bin2hex(random_bytes(4));
    mkdir($dir);
    file_put_contents($dir . '/index.md', "# X\n");
    file_put_contents(
      $dir . '/CHANGELOG.md',
      "# Changelog\n\n## [1.0.0] - 2026-11-01\n\n### Added\n\n"
      . "- See the [guide][g] and [below](#added).\n\n[g]: https://example.org/guide\n",
    );
    $library = new Library(['x' => ['dir' => $dir, 'label' => 'X', 'changelog' => $dir . '/CHANGELOG.md']]);
    $url = static fn (string $path, array $query, ?string $fragment): string => (
      '/' . $path . '?p=' . ($query['p'] ?? '')
    );
    $html = (new WhatsNew($library, new RenderCache(NULL), $url))->entries()[0]['html'];
    $this->assertStringContainsString('<a href="https://example.org/guide">guide</a>', $html);
    $this->assertStringContainsString('<a href="/civicrm/handbook?p=x/changes#added">below</a>', $html);
  }

  public function testHealthySiteRaisesNoUpdateNotice(): void {
    \Civi::settings()->revert(Updates::SETTING);
    $this->assertSame([], $this->checkNames(), 'a first look is a baseline');
    $this->assertSame([], $this->checkNames(), 'unchanged versions stay quiet');
  }

  public function testUpdatedExtensionIsNamedWithALinkToWhatsNew(): void {
    \Civi::settings()->set(Updates::SETTING, ['handbook' => ['version' => '0.0.1', 'since' => NULL]]);
    $event = $this->check();
    $this->assertSame(['handbook_updates'], array_map(static fn ($m) => $m->getName(), $event->messages));
    $message = $event->messages[0]->getMessage();
    $this->assertStringContainsString('Handbook', $message);
    $this->assertStringContainsString('civicrm/handbook/whats-new', $message);
    $this->assertSame(\CRM_Utils_Check::severityMap(\Psr\Log\LogLevel::INFO), $event->messages[0]->getLevel());
  }

  public function testFilteredCheckLeavesTheNoticeOut(): void {
    \Civi::settings()->set(Updates::SETTING, ['handbook' => ['version' => '0.0.1', 'since' => NULL]]);
    $this->assertSame([], array_map(static fn ($m) => $m->getName(), $this->check(['checkPhpVersion'])->messages));
    $this->assertSame(
      ['handbook_updates'],
      array_map(static fn ($m) => $m->getName(), $this->check(['handbook_updates'])->messages),
    );
  }

  public function testOnlyChangelogsTheScreenShowsAreTracked(): void {
    $sources = [];
    foreach (['own', 'generated', 'unreleased'] as $key) {
      $dir = sys_get_temp_dir() . '/handbook-wn-' . bin2hex(random_bytes(4));
      mkdir($dir);
      file_put_contents($dir . '/index.md', "# {$key}\n");
      file_put_contents($dir . '/CHANGELOG.md', "# Changelog\n\n## [1.0.0] - 2026-11-01\n\n- X.\n");
      $sources[$key] = ['dir' => $dir, 'label' => $key, 'changelog' => $dir . '/CHANGELOG.md'];
    }
    file_put_contents($sources['own']['dir'] . '/changes.md', "# Release notes\n");
    file_put_contents($sources['unreleased']['changelog'], "# Changelog\n\n## [Unreleased]\n\n- Pending.\n");
    $this->assertSame(['generated'], Updates::shown(new Library($sources), new RenderCache(NULL)));
  }

  /**
   * @param list<string> $names
   */
  private function check(array $names = []): \Civi\Core\Event\GenericHookEvent {
    $event = \Civi\Core\Event\GenericHookEvent::create([
      'messages' => [],
      'statusNames' => $names,
      'includeDisabled' => FALSE,
    ]);
    StatusCheck::onCheck($event);
    return $event;
  }

  /**
   * @return list<string>
   */
  private function checkNames(): array {
    return array_map(static fn ($m) => $m->getName(), $this->check()->messages);
  }

}
