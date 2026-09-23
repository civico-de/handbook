// axe-core colour contrast over the handbook screens in the classic theme and
// in Riverlea light and dark. Scoped to the extension's own markup: the footer
// and the search button (a plain core-styled button) are theme styling.
import AxeBuilder from "@axe-core/playwright";
import { test, expect } from "@playwright/test";
import { cv, getSetting, setSetting, loginUrl } from "./stack.mjs";

const THEMES = [
  { name: "classic", theme: "greenwich", dark: "light" },
  { name: "Riverlea light", theme: "minetta", dark: "light" },
  { name: "Riverlea dark", theme: "minetta", dark: "dark" },
];
const SCREENS = [
  { name: "index", route: "civicrm/handbook", scope: ".handbook" },
  { name: "page with sections", route: "civicrm/handbook?p=handbook/benutzen", scope: ".handbook" },
  { name: "search", route: "civicrm/handbook/search?term=Handbuch", scope: ".handbook" },
  { name: "search results", route: "civicrm/handbook/search?term=Fixturewort", scope: ".handbook" },
  { name: "fixture page", route: "civicrm/handbook?p=extra/alpha", scope: ".handbook" },
  // tests/fixtures/extra/alpha.md links the dashboard: only the context link there is ours.
  { name: "context link", route: "civicrm/dashboard", scope: ".handbook-context" },
];
const RULES = ["color-contrast", "link-in-text-block"];

const EXTRA = "/var/www/html/ext/handbook/tests/fixtures/extra";
const SETTINGS = ["theme_backend", "riverlea_dark_mode_backend", "handbook_extra_dir"];
let saved;

test.beforeAll(() => {
  saved = Object.fromEntries(SETTINGS.map((name) => [name, getSetting(name)]));
  setSetting("handbook_extra_dir", EXTRA);
});

test.afterAll(() => {
  for (const name of SETTINGS) {
    setSetting(name, saved[name]);
  }
  cv("flush");
});

for (const t of THEMES) {
  test.describe(t.name, () => {
    test.beforeAll(() => {
      setSetting("theme_backend", t.theme);
      setSetting("riverlea_dark_mode_backend", t.dark);
      cv("flush");
    });

    for (const s of SCREENS) {
      test(`${s.name} has no contrast violations`, async ({ page }) => {
        await page.goto(loginUrl(s.route));
        await expect(page.locator(s.scope).first()).toBeVisible();
        const results = await new AxeBuilder({ page })
          .include(s.scope)
          .exclude(".handbook-search button")
          .withRules(RULES)
          .analyze();
        const found = results.violations.flatMap((v) =>
          v.nodes.map(
            (n) => `${v.id}: ${n.target.join(" ")} ${n.any.map((c) => c.message).join(" ")}`,
          ),
        );
        expect(found).toEqual([]);
      });
    }
  });
}

for (const t of THEMES) {
  test(`content lists keep their markers in ${t.name}`, async ({ page }) => {
    setSetting("theme_backend", t.theme);
    setSetting("riverlea_dark_mode_backend", t.dark);
    cv("flush");
    await page.goto(loginUrl("civicrm/handbook?p=extra/alpha"));
    const style = (sel) =>
      page
        .locator(sel)
        .first()
        .evaluate((el) => {
          const s = getComputedStyle(el);
          return [s.listStyleType, parseFloat(s.paddingInlineStart) > 0];
        });
    expect(await style(".handbook-content ul")).toEqual(["disc", true]);
    expect(await style(".handbook-content ul ol")).toEqual(["decimal", true]);
  });
}

test.describe("keyboard and landmarks", () => {
  test.beforeAll(() => {
    setSetting("theme_backend", "minetta");
    setSetting("riverlea_dark_mode_backend", "light");
    cv("flush");
  });

  test("navigation landmarks have distinct labels", async ({ page }) => {
    await page.goto(loginUrl("civicrm/handbook?p=handbook/anleitungen-schreiben"));
    const labels = await page
      .locator(".handbook nav")
      .evaluateAll((navs) =>
        navs.map((n) => n.getAttribute("aria-label") ?? n.getAttribute("aria-labelledby")),
      );
    expect(labels).toHaveLength(4);
    expect(new Set(labels).size).toBe(labels.length);
  });

  test("a source group opens and closes from the keyboard", async ({ page }) => {
    await page.goto(loginUrl("civicrm/handbook?p=handbook/anleitungen-schreiben"));
    const closed = page.locator(".handbook-group:not([open]) > summary").first();
    await expect(closed.getByRole("heading", { level: 2 })).toHaveCount(1);
    await closed.focus();
    await expect(closed).toBeFocused();
    await page.keyboard.press("Enter");
    await expect(page.locator(".handbook-group:not([open])")).toHaveCount(0);
    await page.keyboard.press("Space");
    await expect(page.locator(".handbook-group:not([open])")).toHaveCount(1);
  });

  test("every sidebar section heading sits below its source's h2", async ({ page }) => {
    await page.goto(loginUrl("civicrm/handbook?p=handbook/anleitungen-schreiben"));
    const orphans = await page
      .locator(".handbook-toc h3")
      .evaluateAll(
        (hs) => hs.filter((h) => !h.closest("details")?.querySelector("summary > h2")).length,
      );
    expect(orphans).toBe(0);
    expect(await page.locator(".handbook-toc h3").count()).toBeGreaterThan(0);
  });

  test("narrow screens stack without horizontal scrolling", async ({ page }) => {
    await page.setViewportSize({ width: 360, height: 800 });
    for (const route of [
      "civicrm/handbook",
      "civicrm/handbook?p=extra/alpha",
      "civicrm/handbook/search?term=Fixturewort",
    ]) {
      await page.goto(loginUrl(route));
      const overflow = await page
        .locator(".handbook")
        .evaluate((el) => el.scrollWidth - el.clientWidth);
      expect(overflow, route).toBeLessThanOrEqual(0);
    }
  });
});
