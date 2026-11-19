<?php

declare(strict_types = 1);

namespace Civi\Handbook;

/**
 * Extensions whose changelog the handbook shows and whose version change was
 * noticed in the last DAYS days. The versions seen, and the day each change
 * was noticed, live in the hidden setting handbook_seen_versions.
 */
final class Updates {

  public const SETTING = 'handbook_seen_versions';

  public const DAYS = 14;

  /**
   * Records the installed versions and returns the recently updated extension keys.
   *
   * @return list<string>
   */
  public static function fromSystem(?string $today = NULL): array {
    $mapper = \CRM_Extension_System::singleton()->getMapper();
    $current = [];
    foreach (self::shown(Library::fromSystem(), RenderCache::fromSystem()) as $key) {
      try {
        $current[$key] = (string) $mapper->keyToInfo($key)->version;
      }
      catch (\CRM_Extension_Exception $e) {
        continue;
      }
    }
    $settings = \Civi::settings();
    $stored = $settings->get(self::SETTING);
    $seen = is_array($stored) ? $stored : [];
    $today ??= date('Y-m-d');
    $tracked = self::track($current, $seen, $today);
    if ($tracked !== $seen) {
      $settings->set(self::SETTING, $tracked);
    }
    return self::recent($tracked, $today);
  }

  /**
   * Sources with an entry on "What's new", where the notice links.
   *
   * @return list<string>
   */
  public static function shown(Library $library, RenderCache $cache): array {
    return array_column((new WhatsNew($library, $cache))->entries(), 'source');
  }

  /**
   * The seen versions after this run. A key seen for the first time is a
   * new install, not an update, so it carries no date.
   *
   * @param array<string, string> $current
   *   Installed version per extension key.
   * @param array<array-key, mixed> $seen
   *   The stored map: key => ['version' => string, 'since' => 'Y-m-d'|null].
   *
   * @return array<string, array{version: string, since: string|null}>
   */
  public static function track(array $current, array $seen, string $today): array {
    $tracked = [];
    foreach ($current as $key => $version) {
      $before = $seen[$key] ?? NULL;
      $known = is_array($before) && is_string($before['version'] ?? NULL);
      if ($known && $before['version'] === $version) {
        $since = $before['since'] ?? NULL;
        $tracked[$key] = ['version' => $version, 'since' => is_string($since) ? $since : NULL];
        continue;
      }
      $tracked[$key] = ['version' => $version, 'since' => $known ? $today : NULL];
    }
    return $tracked;
  }

  /**
   * @param array<string, array{version: string, since: string|null}> $tracked
   *
   * @return list<string>
   */
  public static function recent(array $tracked, string $today): array {
    $cutoff = date('Y-m-d', (int) strtotime($today . ' -' . self::DAYS . ' days'));
    return array_keys(array_filter(
      $tracked,
      static fn (array $entry): bool => $entry['since'] !== NULL && $entry['since'] > $cutoff,
    ));
  }

}
